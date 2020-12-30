<?php

namespace App\Handler\Messages;

use App\Entity\Goal;
use App\Entity\User;

class SubmitOrdersMessage
{
    public string $goalId;
    public string $userId;

    public function __construct(User $user, Goal $goal)
    {
        $this->userId = $user->getId();
        $this->goalId = $goal->getId();
    }
}