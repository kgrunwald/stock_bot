<?php

namespace App\Repository;

use App\Entity\User;
use DateTime;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class UserRepository extends DynamoRepository
{
    public function getType()
    {
        return User::class;
    }

    public function getByAccountId(string $accountId): ?User
    {
        return $this->getByKeys($accountId, $accountId);
    }
}
