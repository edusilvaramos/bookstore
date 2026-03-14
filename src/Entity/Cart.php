<?php

namespace App\Entity;

use App\Repository\CartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CartRepository::class)]
class Cart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'cart', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?User $user = null;

 
    #[ORM\OneToMany(mappedBy: 'cart', targetEntity: CartItem::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $items;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\LessThanOrEqual('now')]
    private ?\DateTimeImmutable $addAt = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->addAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        if ($user !== null && $user->getCart() !== $this) {
            $user->setCart($this);
        }

        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CartItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCart($this);
        }

        return $this;
    }

    public function removeItem(CartItem $item): static
    {
        if ($this->items->removeElement($item) && $item->getCart() === $this) {
            $item->setCart(null);
        }

        return $this;
    }

    public function findItemByBook(Book $book): ?CartItem
    {
        foreach ($this->items as $item) {
            $itemBook = $item->getBook();
            if ($itemBook !== null && $book !== null && $itemBook->getId() === $book->getId()) {
                return $item;
            }
        }

        return null;
    }

    public function getAddAt(): ?\DateTimeImmutable
    {
        return $this->addAt;
    }

    public function setAddAt(\DateTimeImmutable $addAt): static
    {
        $this->addAt = $addAt;

        return $this;
    }

}
