<?php

namespace App\Entity;

use App\Repository\AddressRepository;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\User;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    private const NO_TAGS_PATTERN = '/^[^<>]*$/';
    private const INVALID_CHARACTERS_MESSAGE = 'This value contains invalid characters.';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Assert\Regex(pattern: self::NO_TAGS_PATTERN, message: self::INVALID_CHARACTERS_MESSAGE)]
    private ?string $label = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    #[Assert\Regex(pattern: self::NO_TAGS_PATTERN, message: self::INVALID_CHARACTERS_MESSAGE)]
    private ?string $streetLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: self::NO_TAGS_PATTERN, message: self::INVALID_CHARACTERS_MESSAGE)]
    private ?string $streetLine2 = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Assert\Regex(pattern: self::NO_TAGS_PATTERN, message: self::INVALID_CHARACTERS_MESSAGE)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Assert\Regex(pattern: self::NO_TAGS_PATTERN, message: self::INVALID_CHARACTERS_MESSAGE)]
    private ?string $stateOrRegion = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 20)]
    #[Assert\Regex(pattern: '/^[A-Za-z0-9\-\s]{3,20}$/', message: 'Please enter a valid postal code.')]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 2)]
    #[Assert\Regex(pattern: '/^[A-Z]{2}$/', message: 'Country code must use 2 uppercase letters (ISO-3166-1 alpha-2).')]
    private ?string $countryCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: self::NO_TAGS_PATTERN, message: self::INVALID_CHARACTERS_MESSAGE)]
    private ?string $additionalInfo = null;

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

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getStreetLine1(): ?string
    {
        return $this->streetLine1;
    }

    public function setStreetLine1(string $streetLine1): static
    {
        $this->streetLine1 = $streetLine1;

        return $this;
    }

    public function getStreetLine2(): ?string
    {
        return $this->streetLine2;
    }

    public function setStreetLine2(?string $streetLine2): static
    {
        $this->streetLine2 = $streetLine2;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getStateOrRegion(): ?string
    {
        return $this->stateOrRegion;
    }

    public function setStateOrRegion(string $stateOrRegion): static
    {
        $this->stateOrRegion = $stateOrRegion;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): static
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): static
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }
        public function getFullAddress(): string
    {
        $parts = [
            $this->streetLine1,
            $this->streetLine2,
            $this->city,
            $this->stateOrRegion,
            $this->postalCode,
            $this->countryCode
        ];
        return implode(', ', array_filter($parts));
    }
}
