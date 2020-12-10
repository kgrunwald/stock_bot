<?php

namespace App\Repository;

use App\Entity\Goal;
use App\Entity\User;
use Aws\DynamoDb\Exception\DynamoDbException;
use DateTime;

class GoalRepository extends DynamoRepository
{

    public function getType()
    {
        return Goal::class;
    }

    public function getById(string $id): ?Goal
    {
        return $this->getByKeys($id, $id);
    }

    public function getAllGoalsForUser(User $user): array
    {
        try {
            $params = [
                ':userId' => $user->getId(),
                ':prefix' => 'G#'
            ];

            $result = $this->dbClient->query([
                'TableName' => DynamoRepository::TABLENAME,
                'IndexName' => DynamoRepository::GSI1,
                'KeyConditionExpression' => '#pk = :userId and begins_with(#sk, :prefix)',
                'ExpressionAttributeNames' => ['#pk' => 'GSI1', '#sk' => 'PK'],
                'ExpressionAttributeValues' => $this->marshaler->marshalItem($params)
            ]);

            return $this->unmarshalArray($result);
        } catch (DynamoDbException $e) {
            $this->logger->warning('Error getting goals', ['e' => $e->getMessage()]);
            return [];
        }
    }
}
