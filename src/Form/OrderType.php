<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\Order;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
                ->add('totalAmount', IntegerType::class, [
                    'label' => 'Total amount (cents)',
                    'help' => 'Example: 1999 = 19.99 in your currency.',
                    'attr' => [
                        'min' => 0,
                        'step' => 1,
                        'placeholder' => '0',
                    ],
                ])
                ->add('status', TextType::class, [
                    'label' => 'Order status',
                    'attr' => [
                        'placeholder' => 'pending / paid / shipped / cancelled',
                    ],
                ])
                ->add('createdAt', DateType::class, [
                'widget' => 'single_text',
                    'label' => 'Created at',
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                    'choice_label' => fn (User $user): string => sprintf('#%d - %s', $user->getId(), $user->getEmail() ?? 'unknown'),
                    'label' => 'Customer',
                    'placeholder' => 'Select a customer',
            ])
            ->add('orderItems', EntityType::class, [
                'class' => Book::class,
                    'choice_label' => fn (Book $book): string => sprintf('#%d - %s', $book->getId(), $book->getTitle() ?? 'Untitled'),
                'multiple' => true,
                    'label' => 'Books',
                    'help' => 'Hold Ctrl/Cmd to select multiple books.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
