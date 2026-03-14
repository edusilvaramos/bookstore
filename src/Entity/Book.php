<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    private const NO_TAGS_PATTERN = '/^[^<>]*$/';
    private const INVALID_CHARACTERS_MESSAGE = 'This value contains invalid characters.';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Assert\Regex(pattern: self::NO_TAGS_PATTERN, message: self::INVALID_CHARACTERS_MESSAGE)]
    private ?string $title = null;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'At least one author is required.')]
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Length(max: 255),
        new Assert\Regex(pattern: self::NO_TAGS_PATTERN, message: self::INVALID_CHARACTERS_MESSAGE),
    ])]
    private array $authors = [];

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 20, max: 5000)]
    #[Assert\Regex(pattern: self::NO_TAGS_PATTERN, message: self::INVALID_CHARACTERS_MESSAGE)]
    private ?string $description = null;

    #[ORM\Column(length: 13)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 13, max: 13)]
    #[Assert\Regex(pattern: '/^\d{13}$/', message: 'ISBN-13 must contain exactly 13 digits.')]
    private ?string $isbn13 = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $price = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $stock = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Url(protocols: ['http', 'https'])]
    private ?string $coverUrl = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\LessThanOrEqual('today')]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, CartItem>
     */
    #[ORM\OneToMany(mappedBy: 'book', targetEntity: CartItem::class, orphanRemoval: true)]
    private Collection $cartItems;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\ManyToMany(targetEntity: Order::class, mappedBy: 'orderItems')]
    private Collection $orders;

    public function __construct()
    {
        $this->cartItems = new ArrayCollection();
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @param list<string> $authors
     */
    public function setAuthors(array $authors): static
    {
        $this->authors = $authors;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getIsbn13(): ?string
    {
        return $this->isbn13;
    }

    public function setIsbn13(string $isbn13): static
    {
        $this->isbn13 = $isbn13;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    public function setCoverUrl(string $coverUrl): static
    {
        $this->coverUrl = $coverUrl;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function addCartItem(CartItem $cartItem): static
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems->add($cartItem);
            $cartItem->setBook($this);
        }

        return $this;
    }

    public function removeCartItem(CartItem $cartItem): static
    {
        if ($this->cartItems->removeElement($cartItem) && $cartItem->getBook() === $this) {
            $cartItem->setBook(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->addOrderItem($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            $order->removeOrderItem($this);
        }

        return $this;
    }
}
