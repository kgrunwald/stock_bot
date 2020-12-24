<?php

namespace App\Handler\Messages;

use App\Entity\Goal;

class ReconcileGoalMessage
{
    public string $goalId;

    public function __construct(Goal $goal)
    {
        $this->goalId = $goal->getId();
    }
}