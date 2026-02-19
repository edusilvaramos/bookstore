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
            ->addArgument('password', InputArgument::REQUIRED, 'Admin password')
            ->addArgument('firstName', InputArgument::OPTIONAL, 'Admin first name', 'Admin')
            ->addArgument('lastName', InputArgument::OPTIONAL, 'Admin last name', 'User')
            ->addArgument('phone', InputArgument::OPTIONAL, 'Admin phone', '+33000000000')
            ->addArgument('dateOfBirth', InputArgument::OPTIONAL, 'Admin birth date (YYYY-MM-DD)', '1990-01-01');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = strtolower(trim((string) $input->getArgument('email')));
        $plainPassword = (string) $input->getArgument('password');
        $firstName = trim((string) $input->getArgument('firstName'));
        $lastName = trim((string) $input->getArgument('lastName'));
        $phone = trim((string) $input->getArgument('phone'));
        $dateOfBirthInput = trim((string) $input->getArgument('dateOfBirth'));

        $dateOfBirth = \DateTimeImmutable::createFromFormat('Y-m-d', $dateOfBirthInput);
        if (!$dateOfBirth || $dateOfBirth->format('Y-m-d') !== $dateOfBirthInput) {
            $output->writeln('<error>Invalid dateOfBirth format. Use YYYY-MM-DD.</error>');
            return Command::FAILURE;
        }

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
        $user->setFirstName($firstName !== '' ? $firstName : 'Admin');
        $user->setLastName($lastName !== '' ? $lastName : 'User');
        $user->setPhone($phone !== '' ? $phone : '+33000000000');
        $user->setDateOfBirth($dateOfBirth);

        $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln(sprintf('<info>OK</info> Admin created: %s', $email));
        return Command::SUCCESS;
    }
}
