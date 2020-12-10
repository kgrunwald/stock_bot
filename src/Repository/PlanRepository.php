<?php

namespace App\Repository;

use App\Entity\Plan;
use Aws\DynamoDb\Exception\DynamoDbException;

class PlanRepository extends DynamoRepository
{
    public function getById(string $id): ?Plan
    {
        return $this->getByKeys($id, $id);
    }

    public function getAll(): array
    {
        try {
            $params = [
                ':name' => 'plan'
            ];

            $result = $this->dbClient->query([
                'TableName' => DynamoRepository::TABLENAME,
                'IndexName' => DynamoRepository::GSI2,
                'KeyConditionExpression' => '#pk = :name',
                'ExpressionAttributeNames'=> [ '#pk' => 'GSI2' ],
                'ExpressionAttributeValues'=> $this->marshaler->marshalItem($params)
            ]);

            return $this->unmarshalArray($result);
        } catch(DynamoDbException $e) {
            $this->logger->warning('Error getting plans', ['e' => $e->getMessage()]);
            return [];
        }
    }
}
