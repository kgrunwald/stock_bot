<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Security;

class SecurityService
{
    private Security $security;
    private ?User $user;

    public function __construct(Security $security)
    {
        $this->security = $security;
        $this->user = null;
    }

    public function getUser(): ?User
    {
        if($this->user) {
            return $this->user;
        }
        
        return $this->security->getUser();
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }
}