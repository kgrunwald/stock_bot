<?php

namespace App\Repository;

use App\Entity\Plan;
use Aws\DynamoDb\Exception\DynamoDbException;

class PlanRepository extends DynamoRepository
{
    const SORT_KEY_PLAN = 'plan';

    public function getType()
    {
        return Plan::class;
    }

    public function getPK($plan)
    {
        /** @var Plan $plan */
        return $plan->getId();
    }

    public function getSK($item)
    {
        return self::SORT_KEY_PLAN;
    }

    public function getExtraAttributes(): array
    {
        return ['GSI1' => 'plan'];
    }

    public function getById(string $id): ?Plan
    {
        return $this->getByKeys($id, self::SORT_KEY_PLAN);
    }

    public function getAll(): array
    {
        try {
            $params = [
                ':name' => 'plan'
            ];

            $result = $this->dbClient->query([
                'TableName' => DynamoRepository::TABLENAME,
                'IndexName' => DynamoRepository::GSI1,
                'KeyConditionExpression' => '#pk = :name',
                'ExpressionAttributeNames'=> [ '#pk' => 'GSI1' ],
                'ExpressionAttributeValues'=> $this->marshaler->marshalItem($params)
            ]);

            return $this->unmarshalArray($result);
        } catch(DynamoDbException $e) {
            $this->logger->warning('Error getting plans', ['e' => $e->getMessage()]);
            return [];
        }
    }
}
