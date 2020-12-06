<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Security;

class SecurityService
{
    private Security $security;
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getUser(): ?User
    {
        return $this->security->getUser();
    }
}