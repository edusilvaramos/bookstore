<?php

namespace App\Twig;

use App\Repository\CartRepository;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class CartExtension extends AbstractExtension implements GlobalsInterface
{
    private CartRepository $cartRepository;
    private TokenStorageInterface $tokenStorage;

    public function __construct(CartRepository $cartRepository, TokenStorageInterface $tokenStorage)
    {
        $this->cartRepository = $cartRepository;
        $this->tokenStorage = $tokenStorage;
    }

    public function getGlobals(): array
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        if ($user === null || !is_object($user)) {
            return ['cart_item_count' => 0];
        }

        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if ($cart === null) {
            return ['cart_item_count' => 0];
        }

        $count = 0;
        foreach ($cart->getItems() as $item) {
            $count += $item->getQuantity();
        }

        return ['cart_item_count' => $count];
    }
}
