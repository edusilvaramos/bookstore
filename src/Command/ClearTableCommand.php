<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clear:table',
    description: 'Delete all records from a specific table while keeping the table structure.',
)]
class ClearTableCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('entity', InputArgument::REQUIRED, 'Name of the entity (e.g., Book, Category, Order)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityName = trim((string) $input->getArgument('entity'));
        $entityClass = 'App\\Entity\\' . $entityName;

        if (!class_exists($entityClass)) {
            $io->error("Entity '$entityClass' does not exist.");
            return Command::FAILURE;
        }

        $repo = $this->em->getRepository($entityClass);
        $count = $repo->count([]);

        if ($count === 0) {
            $io->success('The table is already empty.');
            return Command::SUCCESS;
        }

        $io->warning("You are about to delete $count record(s) from the $entityName table and its dependencies (if any). This action cannot be undone!");
        if (!$io->confirm('Are you sure you want to continue?', false)) {
            $io->note('Operation cancelled.');
            return Command::SUCCESS;
        }

        $io->progressStart($count);
        $batchSize = 50;
        $i = 0;
        foreach ($repo->findAll() as $entity) {
            $this->em->remove($entity);
            if ((++$i % $batchSize) === 0) {
                $this->em->flush();
                $this->em->clear();
            }
            $io->progressAdvance();
        }
        $this->em->flush();
        $io->progressFinish();

        $io->success("All records from the $entityName table have been removed.");
        return Command::SUCCESS;
    }
}
