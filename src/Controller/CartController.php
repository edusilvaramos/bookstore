<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Form\CartType;
use App\Repository\BookRepository;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
final class CartController extends AbstractController
{
    #[Route(name: 'app_cart_index', methods: ['GET'])]
    public function index(CartRepository $cartRepository): Response
    {
        $cart = $cartRepository->findOneBy(['user' => $this->getUser()]);
        $books = $cart->getCartItem();
        // dump($books, $cart);
        // die();

        $totalPrice = array_reduce($books->toArray(), function ($total, $book) {
            return $total + $book->getPrice();
        }, 0);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'books' => $books,
            'totalPrice' => $totalPrice,
        ]);
    }

    #[Route('/new', name: 'app_cart_add', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository, CartRepository $cartRepository): Response
    {
        $bookId = $request->query->get('id');
        $book = $bookRepository->find($bookId);
        // dump($book);
        $cart = $cartRepository->findOneBy(['user' => $this->getUser()]);
        //  dump($cart);
        //  die();
        // push the book to the cart
        $cart->addCartItem($book);
        $entityManager->flush();

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['GET'])]
    public function delete(int $id, EntityManagerInterface $entityManager, BookRepository $bookRepository, CartRepository $cartRepository): Response
    {
        $cart = $cartRepository->findOneBy(['user' => $this->getUser()]);
        $book = $bookRepository->find($id);

        if (!$cart || !$book) {
            return $this->redirectToRoute('app_cart_index', [], Response::HTTP_SEE_OTHER);
        }

        $cart->removeCartItem($book);

        $entityManager->flush();

        return $this->redirectToRoute('app_cart_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/{id}', name: 'app_cart_show', methods: ['GET'])]
    public function show(Cart $cart): Response
    {
        return $this->render('cart/show.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_cart_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cart $cart, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CartType::class, $cart);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_cart_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('cart/edit.html.twig', [
            'cart' => $cart,
            'form' => $form,
        ]);
    }
}
