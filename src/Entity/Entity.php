<?php

namespace App\Entity;

use DateTime;

class Entity
{
    protected $id;
    protected $createdAt;
    protected $updatedAt;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $time): self
    {
        $this->createdAt = $time;
        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $time): self
    {
        $this->updatedAt = $time;
        return $this;
    }
}