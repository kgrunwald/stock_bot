<?php

namespace App\Repository;

use App\Entity\Security;

class SecurityRepository extends DynamoRepository
{
    const SORT_KEY_SECURITY = 'security';

    public function getType()
    {
        return Security::class;
    }

    public function getPK($security)
    {
        /** @var Security $security */
        return $security->getSymbol();
    }

    public function getSK($item)
    {
        return self::SORT_KEY_SECURITY;
    }

    public function getBySymbol(string $symbol): ?Security
    {
        return $this->getByKeys($symbol, self::SORT_KEY_SECURITY);
    }
}
