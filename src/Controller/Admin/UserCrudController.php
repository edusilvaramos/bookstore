<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;
    private MailerInterface $mailer;
    private ResetPasswordHelperInterface $resetPasswordHelper;

    public function __construct(UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer, ResetPasswordHelperInterface $resetPasswordHelper)
    {
        $this->passwordHasher = $passwordHasher;
        $this->mailer = $mailer;
        $this->resetPasswordHelper = $resetPasswordHelper;
    }
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email'),
            TextField::new('firstName', 'First name'),
            TextField::new('lastName', 'Last name'),
            TelephoneField::new('phone', 'Phone'),
            DateField::new('dateOfBirth', 'Date of birth'),
            BooleanField::new('isVerified', 'Verified'),
            TextField::new('plainPassword')->hideOnForm(),
            ArrayField::new('roles')->setHelp('Use ROLE_ADMIN for admin users, ROLE_USER for regular users.'),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $plain = $entityInstance->getPlainPassword();

        if (!$plain) {
            $plain = 'ChangeMe123!';
            $entityInstance->setPlainPassword($plain);
        }

        $entityInstance->setPassword(
            $this->passwordHasher->hashPassword($entityInstance, $plain)
        );

        $entityInstance->setIsVerified(true);

        parent::persistEntity($entityManager, $entityInstance);
        $resetToken = $this->resetPasswordHelper->generateResetToken($entityInstance);

        $email = (new TemplatedEmail())
            ->from('edusilvaramos.1998@gmail.com')
            ->to($entityInstance->getEmail())
            ->subject('Your account has been created - Please change your password')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ]);

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // fail silently here; emailing issues shouldn't block admin creation
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        $plain = $entityInstance->getPlainPassword();
        if ($plain) {
            $hashed = $this->passwordHasher->hashPassword($entityInstance, $plain);
            $entityInstance->setPassword($hashed);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}
