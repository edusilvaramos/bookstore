<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user:delete',
    description: 'Delete a specific user from database by email.',
)]
class DeleteUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = strtolower(trim((string) $input->getArgument('email')));

        if ($email === '') {
            $output->writeln('<error>Email cannot be empty.</error>');

            return Command::FAILURE;
        }

        /** @var User|null $user */
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $output->writeln(sprintf('<error>User not found:</error> %s', $email));

            return Command::FAILURE;
        }

        $this->em->remove($user);
        $this->em->flush();

        $output->writeln(sprintf('<info>OK</info> User deleted: %s', $email));

        return Command::SUCCESS;
    }
}
