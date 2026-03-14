<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
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
                    ],
                ])
                ->add('lastName', TextType::class, [
                    'label' => 'Last name',
                    'attr' => [
                        'autocomplete' => 'family-name',
                    ],
                ])
                ->add('phone', TelType::class, [
                'required' => false,
                    'label' => 'Phone',
                    'attr' => [
                        'autocomplete' => 'tel',
                        'placeholder' => '+33 6 12 34 56 78',
                    ],
            ])
                ->add('dateOfBirth', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                    'label' => 'Date of birth',
                    'attr' => [
                        'max' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
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
