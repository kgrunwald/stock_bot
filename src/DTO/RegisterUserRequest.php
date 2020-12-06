<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserRequest
{
    /**
     * @Assert\NotBlank
     */
    public $name;

    /**
     * @Assert\Email
     */
    public $email;

    /**
     * @Assert\NotBlank
     */
    public $token;

}