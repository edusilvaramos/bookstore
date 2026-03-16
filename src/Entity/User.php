<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\ResetPasswordRequest;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
// says to doctrin this entity has an PrePersist
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Please enter your email.')]
    #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT)]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter your first name.')]
    #[Assert\Length(min: 2, max: 255)]
    #[Assert\Regex(pattern: '/^[^<>]*$/', message: 'This value contains invalid characters.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter your last name.')]
    #[Assert\Length(min: 2, max: 255)]
    #[Assert\Regex(pattern: '/^[^<>]*$/', message: 'This value contains invalid characters.')]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter your phone number.')]
    #[Assert\Length(min: 6, max: 255)]
    #[Assert\Regex(pattern: '/^\+?[0-9\s\-().]{6,20}$/', message: 'Please enter a valid phone number.')]
    private ?string $phone = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull(message: 'Please enter your date of birth')]
    #[Assert\LessThan('today', message: 'Date of birth must be in the past')]
    private ?\DateTimeImmutable $dateOfBirth = null;


    #[ORM\Column(length: 255)]
    private ?string $password = null;

    // Non-persisted plain password used for forms; will be hashed before saving
    private ?string $plainPassword = null;

    #[ORM\Column]
    private array $roles = [];


    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'user')]
    private Collection $addresses;


    #[ORM\OneToMany(targetEntity: ResetPasswordRequest::class, mappedBy: 'user', cascade: ['remove'], orphanRemoval: true)]
    private Collection $resetPasswordRequests;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Cart $cart = null;

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user')]
    private Collection $orders;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->resetPasswordRequests = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
    }
    // make cart is automatically created when a new user is created
    #[ORM\PrePersist]
    public function initializeCart(): void
    {
        if (null === $this->cart) {
            $this->setCart(new Cart());
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeImmutable $dateOfBirth): static
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
        // No temporary sensitive data is stored on this entity.
    }


    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function getResetPasswordRequests(): Collection
    {
        return $this->resetPasswordRequests;
    }

    public function addResetPasswordRequest(ResetPasswordRequest $request): static
    {
        if (!$this->resetPasswordRequests->contains($request)) {
            $this->resetPasswordRequests->add($request);
            // the owning side is ResetPasswordRequest::$user; ensure it's set there if needed
        }

        return $this;
    }

    public function removeResetPasswordRequest(ResetPasswordRequest $request): static
    {
        $this->resetPasswordRequests->removeElement($request);

        return $this;
    }

    public function addAddress(Address $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setUser($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): static
    {
        if ($this->addresses->removeElement($address) && $address->getUser() === $this) {
            $address->setUser(null);
        }

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        // unset the owning side of the relation if necessary
        if ($cart === null && $this->cart !== null) {
            $this->cart->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($cart !== null && $cart->getUser() !== $this) {
            $cart->setUser($this);
        }

        $this->cart = $cart;

        return $this;
    }


    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order) && $order->getUser() === $this) {
            $order->setUser(null);
        }

        return $this;
    }
}
