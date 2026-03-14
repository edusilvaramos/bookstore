<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:db:clear',
    description: 'Delete all data from the database while keeping the table structure.',
)]
class ClearDatabaseCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Execute the deletion of all database data'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('force')) {
            $io->warning('This command will delete ALL data from your database.');
            $io->comment('Run again with --force to confirm.');
            $io->text('Example: php bin/console app:db:clear --force');

            return Command::INVALID;
        }

        $platformClass = '';

        try {
            $platformClass = strtolower($this->connection->getDatabasePlatform()::class);
            $this->connection->beginTransaction();

            $schemaManager = method_exists($this->connection, 'createSchemaManager')
                ? $this->connection->createSchemaManager()
                : $this->connection->getSchemaManager();

            $tables = $schemaManager->listTableNames();

            if (empty($tables)) {
                $this->connection->commit();
                $io->success('No tables found in the database.');
                return Command::SUCCESS;
            }

            // Disable foreign key checks depending on the database platform
            $this->disableForeignKeyChecks($platformClass);

            foreach ($tables as $table) {
                $quotedTable = $this->quoteTableName($table);

                if (str_contains($platformClass, 'postgresql')) {
                    $this->connection->executeStatement(sprintf(
                        'TRUNCATE TABLE %s RESTART IDENTITY CASCADE;',
                        $quotedTable
                    ));
                } else {
                    $this->connection->executeStatement(sprintf('DELETE FROM %s;', $quotedTable));
                }
            }

            // Re-enable foreign key checks
            $this->enableForeignKeyChecks($platformClass);

            $this->connection->commit();
            $this->entityManager->clear();

            $io->success('All database data has been deleted successfully.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            // Try to restore constraints even if an error occurs
            try {
                if ($platformClass !== '') {
                    $this->enableForeignKeyChecks($platformClass);
                }
            } catch (\Throwable) {
            }

            $io->error('An error occurred while clearing the database.');
            $io->text($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function disableForeignKeyChecks(string $platformClass): void
    {
        if (str_contains($platformClass, 'postgresql')) {
            $this->connection->executeStatement('SET session_replication_role = replica;');
            return;
        }

        if (str_contains($platformClass, 'mysql')) {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0;');
            return;
        }

        if (str_contains($platformClass, 'sqlite')) {
            $this->connection->executeStatement('PRAGMA foreign_keys = OFF;');
        }
    }

    private function enableForeignKeyChecks(string $platformClass): void
    {
        if (str_contains($platformClass, 'postgresql')) {
            $this->connection->executeStatement('SET session_replication_role = DEFAULT;');
            return;
        }

        if (str_contains($platformClass, 'mysql')) {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1;');
            return;
        }

        if (str_contains($platformClass, 'sqlite')) {
            $this->connection->executeStatement('PRAGMA foreign_keys = ON;');
        }
    }

    private function quoteTableName(string $table): string
    {
        $parts = explode('.', $table);
        $quotedParts = array_map(
            fn (string $part): string => $this->connection->quoteSingleIdentifier($part),
            $parts
        );

        return implode('.', $quotedParts);
    }
}