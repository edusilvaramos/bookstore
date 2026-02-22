<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Book;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_US');
        $books = $manager->getRepository(Book::class)->findAll();
        $users = [];

        for ($i = 1; $i <= 12; $i++) {
            $user = new User();
            $email = $faker->unique()->safeEmail();
            $user->setEmail($email);
            $user->setFirstName($faker->firstName());
            $user->setLastName($faker->lastName());
            $user->setPhone($faker->regexify('[0-9]{10}'));
            $user->setDateOfBirth(\DateTimeImmutable::createFromMutable(
                $faker->dateTimeBetween('-65 years', '-18 years')
            ));
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(true);
            $user->setPassword($this->passwordHasher->hashPassword($user, $email));
            $manager->persist($user);
            $users[] = $user;
            $this->createAddressesForUser($manager, $faker, $user);
            $this->createOrdersForUser($manager, $faker, $user, $books);
        }

        $manager->flush();

        foreach ($users as $user) {
            $this->populateCartForUser($faker, $user, $books);
        }

        $manager->flush();
    }

    private function populateCartForUser(\Faker\Generator $faker, User $user, array $books): void
    {
        $cart = $user->getCart();

        if ($cart === null) {
            return;
        }

        $cart->setAddAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-30 days', 'now')));

        foreach ($this->pickRandomBooks($books, $faker->numberBetween(0, 4)) as $book) {
            $item = (new CartItem())
                ->setBook($book)
                ->setQuantity($faker->numberBetween(1, 3));

            $cart->addItem($item);
        }
    }

    private function createAddressesForUser(ObjectManager $manager, \Faker\Generator $faker, User $user): void
    {
        $addressesCount = $faker->numberBetween(1, 2);

        for ($addressIndex = 0; $addressIndex < $addressesCount; $addressIndex++) {
            $address = new Address();
            $address->setUser($user);
            $address->setLabel($addressIndex === 0 ? 'Home' : 'Work');
            $address->setStreetLine1($faker->streetAddress());
            $address->setStreetLine2($faker->optional()->secondaryAddress());
            $address->setCity($faker->city());
            $address->setStateOrRegion($faker->state());
            $address->setPostalCode($faker->postcode());
            $address->setCountryCode('US');
            $address->setAdditionalInfo($faker->optional()->sentence(6));

            $manager->persist($address);
        }
    }

    private function createOrdersForUser(ObjectManager $manager, \Faker\Generator $faker, User $user, array $books): void
    {
        $ordersCount = $faker->numberBetween(0, 3);

        for ($orderIndex = 0; $orderIndex < $ordersCount; $orderIndex++) {
            $order = new Order();
            $order->setUser($user);
            $order->setStatus($faker->randomElement(['pending', 'paid', 'shipped', 'delivered', 'cancelled']));
            $order->setCreatedAt(\DateTimeImmutable::createFromMutable(
                $faker->dateTimeBetween('-90 days', 'now')
            ));

            $orderBooks = $this->pickRandomBooks($books, $faker->numberBetween(1, 4));
            $totalAmount = 0;

            foreach ($orderBooks as $book) {
                $order->addOrderItem($book);
                $totalAmount += (int) $book->getPrice();
            }

            $order->setTotalAmount($totalAmount);
            $manager->persist($order);
        }
    }

    private function pickRandomBooks(array $books, int $count): array
    {
        if ($books === [] || $count <= 0) {
            return [];
        }

        shuffle($books);

        return array_slice($books, 0, min($count, count($books)));
    }

    public function getDependencies(): array
    {
        return [
            BookFixtures::class,
            AdminUserFixtures::class,
        ];
    }
}
