<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
                ->add('label', TextType::class, [
                    'label' => 'Address label',
                    'attr' => [
                        'placeholder' => 'Home, Office, Delivery point...',
                    ],
                    'help' => 'A short label to identify this address later.',
                ])
                ->add('streetLine1', TextType::class, [
                    'label' => 'Street address',
                    'attr' => [
                        'autocomplete' => 'address-line1',
                        'placeholder' => '123 Main Street',
                    ],
                ])
                ->add('streetLine2', TextType::class, [
                    'label' => 'Address line 2',
                    'required' => false,
                    'attr' => [
                        'autocomplete' => 'address-line2',
                        'placeholder' => 'Apartment, suite, building...',
                    ],
                ])
                ->add('city', TextType::class, [
                    'label' => 'City',
                    'attr' => [
                        'autocomplete' => 'address-level2',
                    ],
                ])
                ->add('stateOrRegion', TextType::class, [
                    'label' => 'State/Region',
                    'attr' => [
                        'autocomplete' => 'address-level1',
                    ],
                ])
                ->add('postalCode', TextType::class, [
                    'label' => 'Postal code',
                    'attr' => [
                        'autocomplete' => 'postal-code',
                        'placeholder' => '75001',
                    ],
                ])
                ->add('countryCode', CountryType::class, [
                    'label' => 'Country',
                    'placeholder' => 'Select a country',
                ])
                ->add('additionalInfo', TextareaType::class, [
                    'label' => 'Additional info',
                    'required' => false,
                    'attr' => [
                        'rows' => 3,
                        'placeholder' => 'Delivery instructions, gate code, etc.',
                    ],
                ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                    'choice_label' => fn (User $user): string => sprintf('#%d - %s', $user->getId(), $user->getEmail() ?? 'unknown'),
                    'label' => 'User',
                    'placeholder' => 'Select a user',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
