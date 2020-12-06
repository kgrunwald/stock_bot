<?php
namespace App\Repository;

use App\Entity\Security;
use App\Entity\User;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

class EntityNameConverter implements AdvancedNameConverterInterface
{
    public function normalize(string $propertyName, string $class = null, string $format = null, array $context = [])
    {
        switch($class) {
            case User::class: return $propertyName === 'status' ? 'GSI2' : $propertyName;
            case Security::class: return $propertyName === 'type' ? 'GSI1' : $propertyName;
        }
        
        return $propertyName;
    }

    public function denormalize(string $propertyName, string $class = null, string $format = null, array $context = [])
    {
        switch($class) {
            case User::class: return $propertyName === 'GSI2' ? 'status' : $propertyName;
            case Security::class: return $propertyName === 'GSI1' ? 'type' : $propertyName;
        }

        return $propertyName;
    }
}