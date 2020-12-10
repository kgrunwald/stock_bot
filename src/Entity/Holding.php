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
        $this->id = uniqid('H#');
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

    public function getValue(): float
    {
        return $this->value;
    }

    public function setCostBasis(float $basis): self
    {
        $this->costBasis = $basis;
        return $this;
    }

    public function getCostBasis(): ?float
    {
        return $this->costBasis;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;
        return $this;
    }
}
