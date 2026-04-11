<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
                ->add('email', EmailType::class, [
                    'label' => 'Email',
                    'attr' => [
                        'autocomplete' => 'email',
                        'placeholder' => 'name@example.com',
                    ],
                ])
            ->add('firstName', TextType::class, [
                    'label' => 'First name',
                    'attr' => [
                        'autocomplete' => 'given-name',
                        'placeholder' => 'John',
                    ],
            ])
            ->add('lastName', TextType::class, [
                    'label' => 'Last name',
                    'attr' => [
                        'autocomplete' => 'family-name',
                        'placeholder' => 'Doe',
                    ],
            ])
            ->add('phone', TelType::class, [
                    'label' => 'Phone',
                    'attr' => [
                        'autocomplete' => 'tel',
                        'placeholder' => '+33 6 12 34 56 78',
                    ],
            ])
            ->add('dateOfBirth', DateType::class, [
                'widget' => 'single_text',
                    'label' => 'Date of birth',
                    'attr' => [
                        'max' => (new \DateTimeImmutable('-18 years'))->format('Y-m-d'),
                    ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                    'label' => 'Password',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'placeholder' => 'At least 8 characters',
                    ],
                    'help' => 'Use a strong password with letters, numbers and symbols.',
                'constraints' => [
                    new NotBlank(message: 'Please enter a password'),
                    new Length(min: 6, minMessage: 'Your password should be at least {{ limit }} characters', max: 4096),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
