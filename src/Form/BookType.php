<?php

namespace App\Form;

use App\Entity\Book;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
                ->add('title', TextType::class, [
                    'label' => 'Title',
                    'attr' => [
                        'placeholder' => 'Book title',
                    ],
                ])
                ->add('authors', null, [
                    'label' => 'Authors',
                    'help' => 'Provide one or more authors according to your current JSON field setup.',
                ])
                ->add('description', TextareaType::class, [
                    'label' => 'Description',
                    'attr' => [
                        'rows' => 5,
                        'placeholder' => 'Brief summary of the book',
                    ],
                ])
                ->add('isbn13', TextType::class, [
                    'label' => 'ISBN-13',
                    'attr' => [
                        'placeholder' => '9781234567890',
                        'maxlength' => 13,
                    ],
                ])
                ->add('price', IntegerType::class, [
                    'label' => 'Price (cents)',
                    'attr' => [
                        'min' => 0,
                        'step' => 1,
                    ],
                ])
                ->add('stock', IntegerType::class, [
                    'label' => 'Stock',
                    'attr' => [
                        'min' => 0,
                        'step' => 1,
                    ],
                ])
                ->add('coverUrl', TextType::class, [
                    'label' => 'Cover URL',
                    'attr' => [
                        'placeholder' => 'https://example.com/cover.jpg',
                    ],
                ])
                ->add('publishedAt', DateType::class, [
                'widget' => 'single_text',
                    'label' => 'Published at',
            ])
                ->add('createdAt', DateType::class, [
                'widget' => 'single_text',
                    'label' => 'Created at',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
