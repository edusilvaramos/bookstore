<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user (idempotent).',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Admin email')
            ->addArgument('password', InputArgument::REQUIRED, 'Admin password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = strtolower(trim((string) $input->getArgument('email')));
        $plainPassword = (string) $input->getArgument('password');

        /** @var User|null $existing */
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existing) {
            // Ensure it has admin role (idempotent)
            $roles = $existing->getRoles();
            if (!in_array('ROLE_ADMIN', $roles, true)) {
                $roles[] = 'ROLE_ADMIN';
                $existing->setRoles(array_values(array_unique($roles)));
                $this->em->flush();
            }

            $output->writeln(sprintf('<info>OK</info> User already exists: %s', $email));
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);

        $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln(sprintf('<info>OK</info> Admin created: %s', $email));
        return Command::SUCCESS;
    }
}
