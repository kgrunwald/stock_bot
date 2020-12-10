<?php

namespace App\Entity;


class Goal extends Entity
{
    private $name;
    private $holdings;
    private $userId;
    private $balance;
    private $plan;
    private $type;

    public function __construct()
    {
        $this->id = uniqid('G#');
        $this->holdings = [];
        $this->balance = 0;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    public function setPlan(Plan $plan): self
    {
        $this->plan = $plan;
        $this->setType($plan->getName());
        return $this;
    }

    public function getType(): string {
        return $this->type;
    }

    public function setType(string $type): self {
        $this->type = $type;
        return $this;
    }

    public function setUserId(string $userId): self {
        $this->userId = $userId;
        return $this;
    }

    public function getUserId(): string 
    {
        return $this->userId;
    }

    public function getHoldings(): array
    {
        return array_values($this->holdings);
    }

    public function addHolding(Holding $holding): self
    {
        $this->holdings[$holding->getSecurity()->getSymbol()] = $holding;
        $this->updateBalance();
        return $this;
    }

    public function removeHolding(Holding $holding): self
    {
        unset($this->holdings[$holding->getSecurity()->getSymbol()]);
        $this->updateBalance();
        return $this;
    }

    public function setBalance(float $balance): self 
    {
        $this->balance = $balance;
        return $this;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function updateBalance()
    {
        $this->balance = 0;

        /** @var Holding $holding */
        foreach($this->holdings as $holding)
        {
            $this->balance += $holding->getValue();
        }
    }
}
