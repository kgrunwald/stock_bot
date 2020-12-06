<?php

namespace App\Entity;

class Allocation
{
    private $security;
    private $percentage;

    public function getSecurity(): Security
    {
        return $this->security;
    }

    public function setSecurity(Security $security): self
    {
        $this->security = $security;

        return $this;
    }

    public function getPercentage(): int
    {
        return $this->percentage;
    }

    public function setPercentage(int $percentage): self
    {
        $this->percentage = $percentage;

        return $this;
    }
}
