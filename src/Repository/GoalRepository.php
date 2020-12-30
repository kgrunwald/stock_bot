<?php

namespace App\Repository;

use App\Entity\Goal;
use App\Entity\Order;
use App\Entity\Plan;
use App\Entity\User;

class GoalRepository extends DynamoRepository
{
    public function getById(User $user, string $id): ?Goal
    {
        return $this->getByKeys($user->getId(), $id);
    }

    public function add($entity, array $context = [])
    {
        assert(false, 'Call to unsupported method. Use addGoal');
    }

    public function addGoal(User $user, Goal $goal)
    {
        $context = [
            'userId' => $user->getId(),
            'PK' => $user->getId()
        ];
        return $this->addUpdateToUnitOfWork($goal, $context);
    }
    
    public function getUserGoalById(User $user, string $goalId): ?Goal
    {
        $params = [
            ':goalId' => $goalId,
            ':userId' => $user->getId()
        ];

        $result = $this->dbClient->query([
            'TableName' => DynamoRepository::TABLENAME,
            'KeyConditionExpression' => '#pk = :userId and begins_with(#sk, :goalId)',
            'ExpressionAttributeNames' => ['#pk' => 'PK', '#sk' => 'SK'],
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
            'KeyConditionExpression' => '#pk = :userId and begins_with(#sk, :prefix)',
            'ExpressionAttributeNames' => ['#pk' => 'PK', '#sk' => 'SK'],
            'ExpressionAttributeValues' => $this->marshaler->marshalItem($params)
        ]);

        return $this->unmarshalArray($result);
    }

    public function getAllByPlan(Plan $plan): array
    {
        $params = [
            ':planId' => $plan->getId(),
            ':prefix' => 'G:'
        ];

        $result = $this->dbClient->query([
            'TableName' => DynamoRepository::TABLENAME,
            'IndexName' => DynamoRepository::GSI1,
            'KeyConditionExpression' => '#pk = :planId and begins_with(#sk, :prefix)',
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
