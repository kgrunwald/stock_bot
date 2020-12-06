<?php

namespace App\Repository;

use App\Entity\User;
use DateTime;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class UserRepository extends DynamoRepository
{
    const SORT_KEY_PROFILE = 'profile';
    const DATE_FORMAT = 'Y-m-d H:i:s';

    public function getType()
    {
        return User::class;
    }

    public function getPK($user)
    {
        /** @var User $user */
        return $user->getId();
    }

    public function getSK($item)
    {
        return self::SORT_KEY_PROFILE;
    }

    public function getContext(): array 
    {
        return [AbstractNormalizer::IGNORED_ATTRIBUTES => ['password', 'salt', 'goals', 'newRecord']];
    }

    public function getByAccountId(string $accountId): ?User
    {
        return $this->getByKeys($accountId, self::SORT_KEY_PROFILE);
    }
}
