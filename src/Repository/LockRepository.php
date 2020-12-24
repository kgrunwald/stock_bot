<?php

namespace App\Repository;

use App\Entity\Entity;
use App\Entity\Lock;
use App\Entity\LockableEntity;
use App\Entity\LockInterface;
use Aws\DynamoDb\Exception\DynamoDbException;
use DateTime;
use Exception;

class LockRepository extends DynamoRepository implements LockInterface
{
    public function acquire(LockableEntity $entity, ?int $ttl = 10): ?Lock
    {
        try {
            $lockId = uniqid();
            $this->dbClient->updateItem([
                'TableName' => self::TABLENAME,
                'Key' => $this->entityItem($entity),
                'UpdateExpression' => 'set #lockId = :lockId, #expire = :expire',
                'ConditionExpression' => '(attribute_not_exists(#expire) or #expire < :ts) and #lastUpdate = :lastUpdate',
                'ExpressionAttributeNames' => ['#expire' => '_lockTTL', '#lockId' => '_lockId', '#lastUpdate' => 'updatedAt'],
                'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                    ':ts' => microtime(true),
                    ':lockId' => $lockId,
                    ':expire' => microtime(true) + $ttl,
                    ':lastUpdate' => $entity->getUpdatedAt()->format(DateTime::RFC3339)
                ])
            ]);
        } catch(Exception $e) {
            $this->logger->info('Error acquiring lock', ['e' => $e->getMessage()]);
            return null;
        }

        return new Lock($lockId, $entity->getId(), $this);
    }

    public function release(Lock $lock)
    {
        try {
            $this->dbClient->updateItem([
                'TableName' => self::TABLENAME,
                'Key' => $this->idItem($lock->getKey()),
                'UpdateExpression' => 'set #expire = :expire, #lockId = :empty',
                'ConditionExpression' => '#lockId = :lockId',
                'ExpressionAttributeNames' => ['#expire' => '_lockTTL', '#lockId' => '_lockId'],
                'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                    ':empty' => null,
                    ':expire' => 0,
                    ':lockId' => $lock->getId()
                ])
            ]);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() !== 'ConditionalCheckFailedException') {
                throw $e;
            }
        }

        $lock->markReleased();
    }

    public function releaseEntity(LockableEntity $entity)
    {
        $this->dbClient->updateItem([
            'TableName' => self::TABLENAME,
            'Key' => $this->idItem($entity->getId()),
            'UpdateExpression' => 'set #expire = :expire, #lockId = :empty',
            'ExpressionAttributeNames' => ['#expire' => '_lockTTL', '#lockId' => '_lockId'],
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                ':empty' => null,
                ':expire' => 0,
            ])
        ]);
    }

    private function entityItem(LockableEntity $e): array
    {
        return $this->idItem($e->getId());
    }

    private function idItem(string $id): array
    {
        $item = [
            'PK' => $id,
            'SK' => $id
        ];

        return $this->marshaler->marshalItem($item);
    }
}
