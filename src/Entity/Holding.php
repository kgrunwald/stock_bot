<?php

namespace App\Entity;


class Holding extends Entity
{
    private $quantity;
    private $value;
    private $costBasis;
    private $symbol;
    private Security $security;

    public function __construct()
    {
        $this->id = uniqid('H:');
        $this->quantity = 0;
        $this->value = 0;
        $this->costBasis = 0;
    }

    public function getSecurity(): Security
    {
        return $this->security;
    }

    public function setSecurity(Security $security): self
    {
        $this->security = $security;
        $this->setSymbol($security->getSymbol());
        return $this;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function setCostBasis(int $basis): self
    {
        $this->costBasis = $basis;
        return $this;
    }

    public function getCostBasis(): ?int
    {
        return $this->costBasis;
    }
}
