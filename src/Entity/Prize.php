<?php

namespace App\Entity;

use App\Repository\PrizeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: PrizeRepository::class)]
class Prize
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private UuidInterface $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private int $pointCost = 0;

    #[ORM\Column]
    private int $weight = 1;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $pointsAward = 0;

    #[ORM\OneToMany(mappedBy: 'prize', targetEntity: SpinResult::class)]
    private Collection $spinResults;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->spinResults = new ArrayCollection();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPointCost(): int
    {
        return $this->pointCost;
    }

    public function setPointCost(int $pointCost): self
    {
        $this->pointCost = $pointCost;
        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getPointsAward(): int
    {
        return $this->pointsAward;
    }

    public function setPointsAward(int $pointsAward): self
    {
        $this->pointsAward = $pointsAward;
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
            $spinResult->setPrize($this);
        }
        return $this;
    }
} 