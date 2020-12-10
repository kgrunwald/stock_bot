<?php

namespace App\Repository;

use App\Entity\User;

class UserRepository extends DynamoRepository
{
    public function getByAccountId(string $accountId): ?User
    {
        return $this->getByKeys($accountId, $accountId);
    }
}
