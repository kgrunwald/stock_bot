<?php

namespace App\Entity;

class NineSigPlan extends Plan
{
    const NAME = '9% Signal';
    const STOCK_FUND = 'TQQQ';
    const BOND_FUND = 'AGG';

    private $target;

    public function getTarget(): int
    {
        return $this->target;
    }

    public function setTarget(int $target): self
    {
        $this->target = $target;
        return $this;
    }
}