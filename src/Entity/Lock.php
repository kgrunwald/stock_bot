<?php

namespace App\Entity;

class Lock
{
    public function __construct(string $id, string $key, LockInterface $lockInterface)
    {
        $this->id = $id;
        $this->key = $key;
        $this->lockInterface = $lockInterface;
        $this->released = false;
    }

    public function __destruct()
    {
        if(!$this->released) {
            $this->lockInterface->release($this);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function markReleased()
    {
        $this->released = true;
    }
}