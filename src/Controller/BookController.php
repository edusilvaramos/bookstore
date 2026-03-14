<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/book')]
final class BookController extends AbstractController
{
    #[Route(name: 'app_book_index', methods: ['GET'])]
    public function index(Request $request, BookRepository $bookRepository, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findBy([], ['name' => 'ASC']);
        $selectedCategoryId = $request->query->getInt('category', 0);
        $selectedCategory = null;

        if ($selectedCategoryId > 0) {
            $selectedCategory = $categoryRepository->find($selectedCategoryId);
        }

        $books = $selectedCategory
            ? $bookRepository->findBy(['category' => $selectedCategory], ['title' => 'ASC'])
            : $bookRepository->findBy([], ['title' => 'ASC']);

        return $this->render('book/index.html.twig', [
            'books' => $books,
            'categories' => $categories,
            'selectedCategoryId' => $selectedCategory?->getId(),
        ]);
    }

    #[Route('/{id}', name: 'app_book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }
}
