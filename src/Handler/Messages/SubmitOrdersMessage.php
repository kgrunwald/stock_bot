<?php

namespace App\Handler\Messages;

class SubmitOrdersMessage
{
    public string $goalId;

    public function __construct(string $goalId)
    {
        $this->goalId = $goalId;
    }
}