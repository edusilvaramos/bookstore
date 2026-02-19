<?php

namespace App\Command;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:books',
    description: 'Seed (fake) books into the database',
)]
class SeedBooksCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('count', InputArgument::OPTIONAL, 'How many books to create?', 50)
            ->addOption('append', null, InputOption::VALUE_NONE, 'Do not purge existing books (default behavior already keeps data).')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Delete all existing books before seeding.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = (int) $input->getArgument('count');
        if ($count <= 0) {
            $io->error('Count must be a positive integer.');
            return Command::FAILURE;
        }

        $faker = Factory::create('en_US');

        $covers = [
            'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f',
            'https://images.unsplash.com/photo-1521587760476-6c12a4b040da',
            'https://images.unsplash.com/photo-1512820790803-83ca734da794',
            'https://images.unsplash.com/photo-1495446815901-a7297e633e8d',
            'https://images.unsplash.com/photo-1512820790803-83ca734da794',
            'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f',
        ];

        if ($input->getOption('purge')) {
            $io->warning('Purging all existing books...');
            // Ajuste o nome da entidade/tabela se necessário
            $this->em->createQuery('DELETE FROM App\Entity\Book b')->execute();
        }

        $io->text(sprintf('Creating %d book(s)...', $count));
        $io->progressStart($count);

        for ($i = 0; $i < $count; $i++) {
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
            $book->setPrice($faker->numberBetween(890, 5990)); // €8,90 a €59,90 (em centavos)
            $book->setStock($faker->numberBetween(0, 40));
            $book->setCoverUrl($faker->randomElement($covers));
            $book->setPublishedAt(\DateTimeImmutable::createFromMutable(
                $faker->dateTimeBetween('-20 years', 'now')
            ));
            $book->setCreatedAt(new \DateTimeImmutable());

            $this->em->persist($book);
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();

        $io->success(sprintf('%d book(s) created successfully!', $count));
        return Command::SUCCESS;
    }
}
