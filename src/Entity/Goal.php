<?php

namespace App\Entity;


class Goal extends Entity
{
    private $name;
    private $plan;
    private $holdings;

    public function __construct()
    {
        $this->holdings = [];
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

    public function getPlan(): Plan
    {
        return $this->plan;
    }

    public function setPlan(Plan $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    public function getHoldings(): array
    {
        return $this->holdings;
    }

    public function addHolding(Holding $holding): self
    {
        $this->removeHolding($holding);
        $this->holdings []= $holding;
        return $this;
    }

    public function removeHolding(Holding $holding): self
    {
        /** @var Holding $value */
        foreach($this->holdings as $key => $value)
        {
            if($value->getSecurity()->getSymbol() === $holding->getSecurity()->getSymbol()) {
                unset($this->holdings[$key]);
            }
        }

        return $this;
    }
}
