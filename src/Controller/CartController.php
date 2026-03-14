<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Book;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Form\CartType;
use App\Repository\BookRepository;
use App\Repository\CartRepository;
use App\Service\ShippingEstimatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
final class CartController extends AbstractController
{
    #[Route(name: 'app_cart_index', methods: ['GET'])]
    public function index(
        Request $request,
        CartRepository $cartRepository,
        ShippingEstimatorService $shippingEstimatorService
    ): Response {
        $cart = $cartRepository->findOneBy(['user' => $this->getUser()]);
        $cartItems = $cart?->getItems() ?? [];

        $totalPrice = array_reduce(
            is_array($cartItems) ? $cartItems : $cartItems->toArray(),
            function ($total, CartItem $item) {
                $book = $item->getBook();

                if ($book === null) {
                    return $total;
                }

                return $total + ($book->getPrice() * $item->getQuantity());
            },
            0
        );

        $shippingData = $this->resolveShippingData($request, $shippingEstimatorService);

        $grandTotal = $totalPrice + ($shippingData['shippingCost'] ?? 0);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'items' => $cartItems,
            'totalPrice' => $totalPrice,
            'addresses' => $shippingData['addresses'],
            'addressLabels' => $shippingData['addressLabels'],
            'selectedAddress' => $shippingData['selectedAddress'],
            'distanceKm' => $shippingData['distanceKm'],
            'shippingCost' => $shippingData['shippingCost'],
            'shippingError' => $shippingData['shippingError'],
            'grandTotal' => $grandTotal,
        ]);
    }

    private function resolveShippingData(
        Request $request,
        ShippingEstimatorService $shippingEstimatorService
    ): array {
        $shippingData = [
            'addresses' => [],
            'addressLabels' => [],
            'selectedAddress' => null,
            'distanceKm' => null,
            'shippingCost' => null,
            'shippingError' => null,
        ];

        $user = $this->getUser();

        if (!$user || !method_exists($user, 'getAddresses')) {
            return $shippingData;
        }

        $shippingData['addresses'] = $this->getAddressesFromUser($user);

        if ($shippingData['addresses'] === []) {
            $shippingData['shippingError'] = 'Add an address to estimate shipping.';
        } else {
            $shippingData['addressLabels'] = $this->buildAddressLabels($shippingData['addresses']);
            $selectedAddress = $this->resolveSelectedAddress($request, $shippingData['addresses']);

            if (!$selectedAddress instanceof Address || $selectedAddress->getId() === null) {
                $shippingData['shippingError'] = 'Invalid address selection.';
            } else {
                $shippingData['selectedAddress'] = $selectedAddress;
                $quote = $this->resolveQuoteForAddress($request, $selectedAddress, $shippingEstimatorService);
                $shippingData['distanceKm'] = $quote['distanceKm'];
                $shippingData['shippingCost'] = $quote['shippingCost'];
                $shippingData['shippingError'] = $quote['error'];
            }
        }

        return $shippingData;
    }

    /**
     * @return array<int, mixed>
     */
    private function getAddressesFromUser(object $user): array
    {
        /** @var mixed $addressCollection */
        $addressCollection = $user->getAddresses();

        return is_array($addressCollection) ? $addressCollection : $addressCollection->toArray();
    }

    /**
     * @param array<int, mixed> $addresses
     *
     * @return array<int, string>
     */
    private function buildAddressLabels(array $addresses): array
    {
        $labels = [];

        foreach ($addresses as $address) {
            if ($address instanceof Address && $address->getId() !== null) {
                $labels[$address->getId()] = $address->getFullAddress();
            }
        }

        return $labels;
    }

    /**
     * @param array<int, mixed> $addresses
     */
    private function resolveSelectedAddress(Request $request, array $addresses): ?Address
    {
        $session = $request->hasSession() ? $request->getSession() : null;
        $selectedAddressId = $request->query->getInt('address_id');

        if ($selectedAddressId <= 0 && $session !== null) {
            $selectedAddressId = (int) $session->get('cart.shipping.selected_address_id', 0);
        }

        return $this->findAddressById($addresses, $selectedAddressId) ?? $addresses[0];
    }

    /**
     * @return array{distanceKm: mixed, shippingCost: mixed, error: mixed}
     */
    private function resolveQuoteForAddress(
        Request $request,
        Address $selectedAddress,
        ShippingEstimatorService $shippingEstimatorService
    ): array {
        $session = $request->hasSession() ? $request->getSession() : null;
        $selectedAddressDbId = (int) $selectedAddress->getId();
        $cachedAddressId = $session ? (int) $session->get('cart.shipping.selected_address_id', 0) : 0;
        $cachedQuote = $session ? $session->get('cart.shipping.quote') : null;

        if ($cachedAddressId === $selectedAddressDbId && $this->isValidCachedQuote($cachedQuote)) {
            return $cachedQuote;
        }

        $quote = $this->normalizeQuote($shippingEstimatorService->estimateForAddress($selectedAddress));

        if ($session !== null) {
            $session->set('cart.shipping.selected_address_id', $selectedAddressDbId);
            $session->set('cart.shipping.quote', $quote);
        }

        return $quote;
    }

    private function isValidCachedQuote(mixed $cachedQuote): bool
    {
        return is_array($cachedQuote)
            && array_key_exists('distanceKm', $cachedQuote)
            && array_key_exists('shippingCost', $cachedQuote)
            && array_key_exists('error', $cachedQuote);
    }

    /**
     * @param array<string, mixed> $quote
     *
     * @return array{distanceKm: mixed, shippingCost: mixed, error: mixed}
     */
    private function normalizeQuote(array $quote): array
    {
        return [
            'distanceKm' => $quote['distanceKm'] ?? null,
            'shippingCost' => $quote['shippingCost'] ?? null,
            'error' => $quote['error'] ?? null,
        ];
    }

    /**
     * @param array<int, mixed> $addresses
     */
    private function findAddressById(array $addresses, int $selectedAddressId): ?Address
    {
        if ($selectedAddressId <= 0) {
            return null;
        }

        foreach ($addresses as $address) {
            if ($address instanceof Address && $address->getId() === $selectedAddressId) {
                return $address;
            }
        }

        return null;
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        BookRepository $bookRepository,
        CartRepository $cartRepository
    ): Response {
        $from = $request->request->get('from');
        $book = $bookRepository->find($id);
        $cart = $cartRepository->findOneBy(['user' => $this->getUser()]);

        if (
            !$cart
            || !$book
            || !$this->isCsrfTokenValid('add_to_cart_' . $id, (string) $request->request->get('_token'))
        ) {
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

        /** @var CartItem $primaryItem */
        $primaryItem = array_shift($matchingItems);

        foreach ($matchingItems as $duplicateItem) {
            $primaryItem->incrementQuantity($duplicateItem->getQuantity());
            $cart->removeItem($duplicateItem);
        }

        return $primaryItem;
    }

    #[Route('/decrease/{id}', name: 'app_cart_decrease', methods: ['GET'])]
    public function decrease(
        int $id,
        EntityManagerInterface $entityManager,
        BookRepository $bookRepository,
        CartRepository $cartRepository
    ): Response {
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

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['GET'])]
    public function delete(
        int $id,
        EntityManagerInterface $entityManager,
        BookRepository $bookRepository,
        CartRepository $cartRepository
    ): Response {
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
    public function edit(
        Request $request,
        Cart $cart,
        EntityManagerInterface $entityManager
    ): Response {
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
