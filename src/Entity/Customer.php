<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private UuidInterface $id;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    #[ORM\Column]
    private int $points = 0;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: SpinResult::class)]
    private Collection $spinResults;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: PointsTransaction::class)]
    private Collection $pointsTransactions;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->spinResults = new ArrayCollection();
        $this->pointsTransactions = new ArrayCollection();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function addPoints(int $amount, string $reason): self
    {
        $this->points += $amount;
        $transaction = new PointsTransaction($this, $amount, $reason);
        $this->pointsTransactions->add($transaction);
        return $this;
    }

    public function deductPoints(int $amount, string $reason): self
    {
        if ($this->points < $amount) {
            throw new \RuntimeException('Insufficient points');
        }
        $this->points -= $amount;
        $transaction = new PointsTransaction($this, -$amount, $reason);
        $this->pointsTransactions->add($transaction);
        return $this;
    }

    /**
     * @return Collection<int, SpinResult>
     */
    public function getSpinResults(): Collection
    {
        return $this->spinResults;
    }

    public function addSpinResult(SpinResult $spinResult): self
    {
        if (!$this->spinResults->contains($spinResult)) {
            $this->spinResults->add($spinResult);
            $spinResult->setCustomer($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, PointsTransaction>
     */
    public function getPointsTransactions(): Collection
    {
        return $this->pointsTransactions;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return $this->getEmail();
    }

    public function eraseCredentials(): void
    {
        // No sensitive data to erase
    }

    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }
} 