<?php

namespace App\Repository;

use App\Entity\Entity;
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

    public abstract function getType();

    protected function unmarshal(Result $result, string $type = null): object
    {
       return $this->unmarshalItem($result['Item'], $type);
    }

    protected function unmarshalArray(Result $result, string $type = null): array
    {
        $objects = [];
        foreach ($result['Items'] as $item) {
            $objects []= $this->unmarshalItem($item, $type);
        }

        return $objects;
    }

    protected function unmarshalItem(array $item, ?string $type = null) {
        $data = $this->marshaler->unmarshalItem($item);

        if ($type === null) {
            $type = $this->getType();
        }

        return $this->denormalizer->denormalize($data, $type);
    }

    public function add($entity)
    {
        $context = ['PK' => $entity->getId()];
        $this->addUpdateToUnitOfWork($entity, $context);
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
        foreach($normalized as $array) {
            $item = $this->marshaler->marshalItem($array);
            $this->putItems []= $item;
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
