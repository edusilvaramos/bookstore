<?php

namespace App\Command;

use App\Entity\Book;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:seed:books',
    description: 'Import books from Google Books API into the database',
)]
class SeedBooksCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('subject', InputArgument::OPTIONAL, 'Google Books subject to import', 'fiction')
            ->addArgument('count', InputArgument::OPTIONAL, 'How many books to import?', 20)
            ->addOption('append', null, InputOption::VALUE_NONE, 'Keep existing books and append new ones.')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Delete all existing books before importing.')
            ->addOption('lang', null, InputOption::VALUE_REQUIRED, 'Language restriction', 'en');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $subject = trim((string) $input->getArgument('subject'));
        $count = (int) $input->getArgument('count');
        $lang = trim((string) $input->getOption('lang'));

        if ($count <= 0 || $count > 40) {
            $io->error('Count must be between 1 and 40 because Google Books maxResults is limited to 40.');
            return Command::FAILURE;
        }

        if ($input->getOption('purge')) {
            $io->warning('Purging existing books...');
            $this->em->createQuery('DELETE FROM App\Entity\Book b')->execute();
        }

        $io->section(sprintf('Importing books from Google Books API (subject: %s, count: %d, lang: %s)', $subject, $count, $lang));

        try {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/books/v1/volumes', [
                'query' => [
                    'q' => 'subject:' . $subject,
                    'maxResults' => $count,
                    'langRestrict' => $lang,
                    'printType' => 'books',
                    'orderBy' => 'relevance',
                ],
            ]);

            $data = $response->toArray();
        } catch (\Throwable $e) {
            $io->error('Google Books API request failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $items = $data['items'] ?? [];
        if ($items === []) {
            $io->warning('No books were returned by Google Books API.');
            return Command::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        $io->progressStart(\count($items));

        foreach ($items as $item) {
            $volumeInfo = $item['volumeInfo'] ?? [];
            $isbn13 = $this->extractIsbn13($volumeInfo);

            if ($isbn13 === null) {
                ++$skipped;
                $io->progressAdvance();
                continue;
            }

            $existingBook = $this->em->getRepository(Book::class)->findOneBy(['isbn13' => $isbn13]);
            if ($existingBook instanceof Book) {
                ++$skipped;
                $io->progressAdvance();
                continue;
            }

            $title = $volumeInfo['title'] ?? 'Untitled';
            $description = $volumeInfo['description'] ?? 'No description available for this book.';
            $authors = $volumeInfo['authors'] ?? ['Unknown author'];
            $coverUrl = $volumeInfo['imageLinks']['thumbnail']
                ?? $volumeInfo['imageLinks']['smallThumbnail']
                ?? 'https://via.placeholder.com/128x192?text=No+Cover';

            $publishedAt = $this->normalizePublishedDate($volumeInfo['publishedDate'] ?? null);
            if ($publishedAt === null) {
                $publishedAt = new \DateTimeImmutable('2000-01-01');
            }

            $categoryName = $this->extractCategoryName($volumeInfo, $subject);
            $category = $this->findOrCreateCategory($categoryName);

            $book = new Book();
            $book->setTitle($title);
            $book->setAuthors($this->normalizeAuthors($authors));
            $book->setDescription($this->normalizeDescription($description));
            $book->setIsbn13($isbn13);
            $book->setPrice(random_int(999, 3999));
            $book->setStock(random_int(5, 30));
            $book->setCoverUrl($this->normalizeCoverUrl($coverUrl));
            $book->setPublishedAt($publishedAt);
            $book->setCreatedAt(new \DateTimeImmutable());
            $book->setCategory($category);

            $this->em->persist($book);
            ++$created;
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();

        $io->newLine(2);
        $io->success(sprintf(
            'Import finished. Created: %d book(s). Skipped: %d book(s).',
            $created,
            $skipped
        ));

        return Command::SUCCESS;
    }

    private function extractIsbn13(array $volumeInfo): ?string
    {
        foreach ($volumeInfo['industryIdentifiers'] ?? [] as $identifier) {
            if (($identifier['type'] ?? null) !== 'ISBN_13') {
                continue;
            }

            $raw = (string) ($identifier['identifier'] ?? '');
            $digits = preg_replace('/\D+/', '', $raw);

            if (\is_string($digits) && \strlen($digits) === 13) {
                return $digits;
            }
        }

        return null;
    }

    private function normalizePublishedDate(?string $publishedDate): ?\DateTimeImmutable
    {
        if (!$publishedDate) {
            return null;
        }

        $formats = ['Y-m-d', 'Y-m', 'Y'];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $publishedDate);
            if ($date instanceof \DateTimeImmutable) {
                return match ($format) {
                    'Y' => $date->setDate((int) $publishedDate, 1, 1),
                    'Y-m' => $date->setDate(
                        (int) substr($publishedDate, 0, 4),
                        (int) substr($publishedDate, 5, 2),
                        1
                    ),
                    default => $date,
                };
            }
        }

        try {
            return new \DateTimeImmutable($publishedDate);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param mixed $authors
     * @return list<string>
     */
    private function normalizeAuthors(mixed $authors): array
    {
        if (!\is_array($authors) || $authors === []) {
            return ['Unknown author'];
        }

        $normalized = [];

        foreach ($authors as $author) {
            if (!\is_string($author)) {
                continue;
            }

            $author = trim(strip_tags($author));

            if ($author !== '') {
                $normalized[] = mb_substr($author, 0, 255);
            }
        }

        return $normalized !== [] ? array_values(array_unique($normalized)) : ['Unknown author'];
    }

    private function normalizeDescription(string $description): string
    {
        $description = trim(strip_tags($description));

        if (mb_strlen($description) < 20) {
            $description .= ' Additional information is not available for this title.';
        }

        return mb_substr($description, 0, 5000);
    }

    private function normalizeCoverUrl(string $coverUrl): string
    {
        $coverUrl = trim($coverUrl);

        if (str_starts_with($coverUrl, 'http://')) {
            $coverUrl = 'https://' . substr($coverUrl, 7);
        }

        return $coverUrl;
    }

    private function extractCategoryName(array $volumeInfo, string $fallback): string
    {
        $category = $volumeInfo['categories'][0] ?? $fallback;
        $category = trim(strip_tags((string) $category));

        if ($category === '') {
            $category = $fallback;
        }

        $category = mb_strtolower($category);
        $category = preg_replace('/\s+/', ' ', $category);
        $category = ucwords($category);

        return mb_substr($category, 0, 100);
    }

    private function findOrCreateCategory(string $name): Category
    {
        $category = $this->em->getRepository(Category::class)->findOneBy(['name' => $name]);

        if ($category instanceof Category) {
            return $category;
        }

        $category = new Category();
        $category->setName($name);

        $this->em->persist($category);

        return $category;
    }
}
