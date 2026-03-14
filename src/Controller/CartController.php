<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Book;
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
        $cartItems = $cart?->getItems() ?? [];

        $totalPrice = array_reduce(is_array($cartItems) ? $cartItems : $cartItems->toArray(), function ($total, CartItem $item) {
            $book = $item->getBook();

            if ($book === null) {
                return $total;
            }

            return $total + ($book->getPrice() * $item->getQuantity());
        }, 0);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'items' => $cartItems,
            'totalPrice' => $totalPrice,
        ]);
    }
    
    // Adds one unit of a book to the authenticated user's cart.
    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $id, Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository, CartRepository $cartRepository): Response
    {
        $from = $request->request->get('from');
        $book = $bookRepository->find($id);
        $cart = $cartRepository->findOneBy(['user' => $this->getUser()]);

        if (!$cart || !$book || !$this->isCsrfTokenValid('add_to_cart_'.$id, (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        $existingItem = $this->mergeDuplicateItems($cart, $book);

        if ($existingItem !== null) {
            $existingItem->incrementQuantity();
        } else {
            $item = (new CartItem())
                ->setBook($book)
                ->setQuantity(1);
            $cart->addItem($item);
        }

        $entityManager->flush();

        if ($from !== 'cart') {
            $this->addFlash('success', 'The book has been added to your cart.');
        }

        if ($from === 'cart') {
            return $this->redirectToRoute('app_cart_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Ensures a cart has at most one line per book by merging duplicate rows.
     */
    private function mergeDuplicateItems(Cart $cart, Book $book): ?CartItem
    {
        $matchingItems = [];

        foreach ($cart->getItems() as $item) {
            $itemBook = $item->getBook();

            if ($itemBook !== null && $itemBook->getId() === $book->getId()) {
                $matchingItems[] = $item;
            }
        }

        if ($matchingItems === []) {
            return null;
        }

        $primaryItem = array_shift($matchingItems);

        foreach ($matchingItems as $duplicateItem) {
            $primaryItem->incrementQuantity($duplicateItem->getQuantity());
            $cart->removeItem($duplicateItem);
        }

        return $primaryItem;
    }

    #[Route('/decrease/{id}', name: 'app_cart_decrease', methods: ['GET'])]
    public function decrease(int $id, EntityManagerInterface $entityManager, BookRepository $bookRepository, CartRepository $cartRepository): Response
    {
        $cart = $cartRepository->findOneBy(['user' => $this->getUser()]);
        $book = $bookRepository->find($id);

        if (!$cart || !$book) {
            return $this->redirectToRoute('app_cart_index', [], Response::HTTP_SEE_OTHER);
        }

        $item = $cart->findItemByBook($book);

        if ($item !== null && $item->getQuantity() > 1) {
            $item->setQuantity($item->getQuantity() - 1);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_cart_index', [], Response::HTTP_SEE_OTHER);
    }

    //  to remove a book line from the cart
    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['GET'])]
    public function delete(int $id, EntityManagerInterface $entityManager, BookRepository $bookRepository, CartRepository $cartRepository): Response
    {
        $cart = $cartRepository->findOneBy(['user' => $this->getUser()]);
        $book = $bookRepository->find($id);

        if (!$cart || !$book) {
            return $this->redirectToRoute('app_cart_index', [], Response::HTTP_SEE_OTHER);
        }

        $item = $cart->findItemByBook($book);

        if ($item !== null) {
            $cart->removeItem($item);
        }

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
