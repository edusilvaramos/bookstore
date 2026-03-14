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

        $platform = $this->connection->getDatabasePlatform()->getName();

        try {
            $this->connection->beginTransaction();

            $schemaManager = method_exists($this->connection, 'createSchemaManager')
                ? $this->connection->createSchemaManager()
                : $this->connection->getSchemaManager();

            $tables = $schemaManager->listTableNames();

            if (empty($tables)) {
                $io->success('No tables found in the database.');
                return Command::SUCCESS;
            }

            // Disable foreign key checks depending on the database platform
            switch ($platform) {
                case 'postgresql':
                    $this->connection->executeStatement('SET session_replication_role = replica;');
                    break;

                case 'mysql':
                    $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0;');
                    break;

                case 'sqlite':
                    $this->connection->executeStatement('PRAGMA foreign_keys = OFF;');
                    break;
            }

            foreach ($tables as $table) {
                if ($platform === 'postgresql') {
                    $this->connection->executeStatement(sprintf(
                        'TRUNCATE TABLE "%s" RESTART IDENTITY CASCADE;',
                        $table
                    ));
                } else {
                    $this->connection->executeStatement(sprintf('DELETE FROM %s;', $table));
                }
            }

            // Re-enable foreign key checks
            switch ($platform) {
                case 'postgresql':
                    $this->connection->executeStatement('SET session_replication_role = DEFAULT;');
                    break;

                case 'mysql':
                    $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1;');
                    break;

                case 'sqlite':
                    $this->connection->executeStatement('PRAGMA foreign_keys = ON;');
                    break;
            }

            $this->connection->commit();
            $this->entityManager->clear();

            $io->success('All database data has been deleted successfully.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            // Try to restore constraints even if an error occurs
            try {
                switch ($platform) {
                    case 'postgresql':
                        $this->connection->executeStatement('SET session_replication_role = DEFAULT;');
                        break;

                    case 'mysql':
                        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1;');
                        break;

                    case 'sqlite':
                        $this->connection->executeStatement('PRAGMA foreign_keys = ON;');
                        break;
                }
            } catch (\Throwable) {
            }

            $io->error('An error occurred while clearing the database.');
            $io->text($e->getMessage());

            return Command::FAILURE;
        }
    }
}