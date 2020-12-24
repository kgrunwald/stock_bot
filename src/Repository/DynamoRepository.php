<?php

namespace App\Repository;

use App\Entity\Entity;
use App\Entity\User;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Aws\Result;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

abstract class DynamoRepository
{
    protected DynamoDbClient $dbClient;
    protected Marshaler $marshaler;
    protected NormalizerInterface $normalizer;
    protected DenormalizerInterface $denormalizer;
    protected LoggerInterface $logger;

    private array $putItems;

    const TABLENAME = 'jk-stockbot';
    const GSI1 = 'GSI1';
    const GSI2 = 'GSI2';

    public function __construct(DynamoDbClient $dbClient, LoggerInterface $logger, NormalizerInterface $normalizer, DenormalizerInterface $denormalizer)
    {
        $this->dbClient = $dbClient;
        $this->logger = $logger;
        $this->marshaler = new Marshaler();
        $this->normalizer = $normalizer;
        $this->denormalizer = $denormalizer;
        $this->putItems = [];
    }

    protected function unmarshal(Result $result): object
    {
        if (isset($result['Item'])) {
            return $this->unmarshalItem($result['Item']);
        }

        $objects = [];
        foreach ($result['Items'] as $item) {
            $objects[] = $this->marshaler->unmarshalItem($item);
        }

        return $this->denormalizer->denormalize($objects, '', EntityNormalizer::FORMAT);
    }

    protected function unmarshalArray(Result $result): array
    {
        $objects = [];
        foreach ($result['Items'] as $item) {
            $objects[] = $this->unmarshalItem($item);
        }

        return $objects;
    }

    protected function unmarshalItem(array $item)
    {
        $data = $this->marshaler->unmarshalItem($item);
        return $this->denormalizer->denormalize($data, $data['_t']);
    }

    public function add($entity, ?User $user = null)
    {
        $context = ['PK' => $entity->getId(), 'userId' => $user && $user->getId()];
        $this->addUpdateToUnitOfWork($entity, $context);
    }

    public function delete($entity, $child = null)
    {
        $this->dbClient->deleteItem([
            'TableName' => self::TABLENAME,
            'Key' => $this->marshaler->marshalItem([
                'PK' => $entity->getId(),
                'SK' => $child ? $child->getId() : $entity->getId(),
            ])
        ]);
    }

    protected function getByKeys(string $pk, string $sk): ?object
    {
        $key = $this->marshaler->marshalItem([
            'PK' => $pk,
            'SK' => $sk
        ]);

        $result = $this->getItem($key);
        if ($result && $result["Item"]) {
            return $this->unmarshal($result);
        }

        return null;
    }

    protected function getItem($key): ?Result
    {
        try {
            return $this->dbClient->getItem([
                'TableName' => self::TABLENAME,
                'Key' => $key
            ]);
        } catch (DynamoDbException $e) {
            $this->logger->warning($e->getMessage());
            return null;
        }
    }

    protected function addUpdateToUnitOfWork($update, array $context = [])
    {
        $normalized = $this->normalizer->normalize($update, EntityNormalizer::FORMAT, $context);
        foreach ($normalized as $array) {
            $item = $this->marshaler->marshalItem($array);
            $this->putItems[] = $item;
        }
    }

    public function getUpdateItems(): array
    {
        return $this->putItems;
    }

    public function clearUnitOfWork()
    {
        $this->putItems = [];
    }
}
