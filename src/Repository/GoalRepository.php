<?php

namespace App\Repository;

use App\Entity\Goal;
use App\Entity\Order;
use App\Entity\User;

class GoalRepository extends DynamoRepository
{
    public function getById(string $id): ?Goal
    {
        return $this->getByKeys($id, $id);
    }
    
    public function getUserGoalById(User $user, string $goalId): ?Goal
    {
        $params = [
            ':goalId' => $goalId,
            ':userId' => $user->getId()
        ];

        $result = $this->dbClient->query([
            'TableName' => DynamoRepository::TABLENAME,
            'KeyConditionExpression' => '#pk = :goalId',
            'FilterExpression' => '#userId = :userId',
            'ExpressionAttributeNames' => ['#pk' => 'PK', '#userId' => 'userId'],
            'ExpressionAttributeValues' => $this->marshaler->marshalItem($params)
        ]);

        return $this->unmarshal($result);
    }

    public function getUserGoals(User $user): array
    {
        $params = [
            ':userId' => $user->getId(),
            ':prefix' => 'G:'
        ];

        $result = $this->dbClient->query([
            'TableName' => DynamoRepository::TABLENAME,
            'IndexName' => DynamoRepository::GSI1,
            'KeyConditionExpression' => '#pk = :userId and begins_with(#sk, :prefix)',
            'ExpressionAttributeNames' => ['#pk' => 'GSI1', '#sk' => 'PK'],
            'ExpressionAttributeValues' => $this->marshaler->marshalItem($params)
        ]);

        return $this->unmarshalArray($result);
    }

    public function removeOrder(Goal $goal, Order $order)
    {
        $this->delete($goal, $order);
        $goal->removeOrder($order);
    }
}
