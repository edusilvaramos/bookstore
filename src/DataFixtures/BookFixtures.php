<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class BookFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_US');

        $covers = [
            'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f',
            'https://images.unsplash.com/photo-1521587760476-6c12a4b040da',
            'https://images.unsplash.com/photo-1512820790803-83ca734da794',
            'https://images.unsplash.com/photo-1495446815901-a7297e633e8d',
            'https://images.unsplash.com/photo-1512820790803-83ca734da794',
            'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f',
        ];

        for ($i = 0; $i < 50; $i++) {

            // 1 a 3 autores diferentes
            $authors = [];
            $nAuthors = $faker->numberBetween(1, 3);
            for ($a = 0; $a < $nAuthors; $a++) {
                $authors[] = $faker->name();
            }

            $book = new Book();
            $book->setTitle($faker->sentence($faker->numberBetween(2, 6)));
            $book->setAuthors($authors);
            $book->setDescription($faker->paragraphs($faker->numberBetween(2, 5), true));
            $book->setIsbn13((string) $faker->isbn13());
            $book->setPrice($faker->numberBetween(890, 5990)); // €8,90 a €59,90
            $book->setStock($faker->numberBetween(0, 40));
            $book->setCoverUrl($faker->randomElement($covers));
            $book->setPublishedAt(\DateTimeImmutable::createFromMutable(
                $faker->dateTimeBetween('-20 years', 'now')
            ));
            $book->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($book);
        }

        $manager->flush();
    }
}
