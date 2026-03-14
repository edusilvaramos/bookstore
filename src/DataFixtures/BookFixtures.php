<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BookFixtures extends Fixture
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $subjects = [
            'fiction',
            'fantasy',
            'history',
            'science',
            'business',
        ];

        $created = 0;

        foreach ($subjects as $subject) {
            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/books/v1/volumes', [
                'query' => [
                    'q' => 'subject:' . $subject,
                    'maxResults' => 20,
                    'langRestrict' => 'en',
                    'printType' => 'books',
                    'orderBy' => 'relevance',
                ],
            ]);

            $data = $response->toArray();

            foreach ($data['items'] ?? [] as $item) {
                $volumeInfo = $item['volumeInfo'] ?? [];

                $isbn13 = $this->extractIsbn13($volumeInfo);
                if ($isbn13 === null) {
                    continue;
                }

                $existingBook = $manager
                    ->getRepository(Book::class)
                    ->findOneBy(['isbn13' => $isbn13]);

                if ($existingBook instanceof Book) {
                    continue;
                }

                $title = $volumeInfo['title'] ?? null;
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
                $category = $this->findOrCreateCategory($manager, $categoryName);

                $book = new Book();
                $book->setTitle($title ?? 'Untitled');
                $book->setAuthors($this->normalizeAuthors($authors));
                $book->setDescription($this->normalizeDescription($description));
                $book->setIsbn13($isbn13);
                $book->setPrice(random_int(999, 3999)); // centavos: 9.99 a 39.99
                $book->setStock(random_int(5, 30));
                $book->setCoverUrl($this->normalizeCoverUrl($coverUrl));
                $book->setPublishedAt($publishedAt);
                $book->setCreatedAt(new \DateTimeImmutable());
                $book->setCategory($category);

                $manager->persist($book);
                ++$created;
            }
        }

        $manager->flush();
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

        return $category !== '' ? mb_substr($category, 0, 100) : ucfirst($fallback);
    }

    private function findOrCreateCategory(ObjectManager $manager, string $name): Category
    {
        $category = $manager->getRepository(Category::class)->findOneBy(['name' => $name]);

        if ($category instanceof Category) {
            return $category;
        }

        $category = new Category();
        $category->setName($name);

        $manager->persist($category);

        return $category;
    }
}