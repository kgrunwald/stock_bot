<?php

namespace App\Entity;

interface LockInterface
{
    function acquire(LockableEntity $entity): ?Lock;
    function release(Lock $lock);
}