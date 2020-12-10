<?php

namespace App\Repository;

use App\Entity\Security;

class SecurityRepository extends DynamoRepository
{
    public function getType()
    {
        return Security::class;
    }

    public function getBySymbol(string $symbol): ?Security
    {
        return $this->getByKeys($symbol, $symbol);
    }
}
