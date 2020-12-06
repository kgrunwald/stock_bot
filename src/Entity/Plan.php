<?php

namespace App\Entity;


class Plan extends Entity
{
    private $allocations;
    private $name;

    public function __construct()
    {
        $this->id = uniqid('P#');
        $this->allocations = [];
    }

    public function getAllocations(): array
    {
        return $this->allocations;
    }

    public function addAllocation(Allocation $allocation): self
    {
        $this->removeAllocation($allocation);
        $this->allocations[] = $allocation;    
        return $this;
    }

    public function removeAllocation(Allocation $allocation): self
    {
        /** @var Allocation $a */
        foreach($this->allocations as $key => $a) {
            if($a->getSecurity()->getSymbol() == $allocation->getSecurity()->getSymbol()) {
                unset($this->allocations[$key]);
            }
        }

        return $this;
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
}
