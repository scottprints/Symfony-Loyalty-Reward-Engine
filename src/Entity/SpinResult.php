<?php

namespace App\Entity;

use App\Repository\SpinResultRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: SpinResultRepository::class)]
class SpinResult
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private UuidInterface $id;

    #[ORM\ManyToOne(inversedBy: 'spinResults')]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\ManyToOne(inversedBy: 'spinResults')]
    #[ORM\JoinColumn(nullable: false)]
    private Prize $prize;

    #[ORM\Column]
    private \DateTimeImmutable $spunAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->spunAt = new \DateTimeImmutable();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getPrize(): Prize
    {
        return $this->prize;
    }

    public function setPrize(Prize $prize): self
    {
        $this->prize = $prize;
        return $this;
    }

    public function getSpunAt(): \DateTimeImmutable
    {
        return $this->spunAt;
    }

    public function setSpunAt(\DateTimeImmutable $spunAt): self
    {
        $this->spunAt = $spunAt;
        return $this;
    }
} 