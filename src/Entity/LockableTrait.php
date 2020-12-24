<?php

namespace App\Entity;

trait LockableTrait {
    private bool $locked = false;

    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;
        return $this;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }
}