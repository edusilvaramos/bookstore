<?php

namespace App\Form;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\DBAL\Types\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
                ->add('addAt', DateType::class, [
                'widget' => 'single_text',
                    'label' => 'Added at',
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
            'data_class' => Cart::class,
        ]);
    }
}
