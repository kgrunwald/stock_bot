<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

class User extends Entity implements UserInterface
{
    const STATUS_ACTIVE = "ACTIVE";

    private $email;
    private $roles = [];
    private $name;
    private $status = self::STATUS_ACTIVE;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): string
    {
        return (string) $this->email ? $this->email : $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPassword()
    {
        // not needed for apps that do not check user passwords
    }

    public function getSalt()
    {
        // not needed for apps that do not check user passwords
    }

    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
