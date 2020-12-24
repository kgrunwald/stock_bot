<?php

namespace App\Entity;

use DateTime;

interface LockableEntity
{
    function getId(): ?string;
    function getUpdatedAt(): DateTime;
    function isLocked(): bool;
    function setLocked(bool $locked): self;
}