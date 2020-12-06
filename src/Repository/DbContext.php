<?php

namespace App\Repository;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\Utils;
use Psr\Log\LoggerInterface;

class DbContext
{
    const TABLENAME = 'jk-stockbot';

    public UserRepository $users;
    public SecurityRepository $securities;
    public PlanRepository $plans;

    private DynamoDbClient $dbClient;

    public function __construct(DynamoDbClient $dbClient, LoggerInterface $logger, UserRepository $userRepo, SecurityRepository $securityRepo, PlanRepository $planRepo)
    {
        $this->dbClient = $dbClient;
        $this->logger = $logger;
        $this->marshaler = new Marshaler();
        $this->users = $userRepo;
        $this->securities = $securityRepo;
        $this->plans = $planRepo;
    }

    public function commit()
    {
        $promise = $this->commitAsync();
        $promise->wait();
    }

    public function commitAsync(): Promise
    {
        $updates = $this->getUpdates();

        $chunks = array_chunk($updates, 25);
        $promises = [];
        foreach($chunks as $chunk)
        {
            $promises []= $this->dbClient->batchWriteItemAsync([
                'RequestItems' => [
                    self::TABLENAME => $chunk
                ]
            ]);
        }

        return Utils::all($promises);
    }

    private function getUpdates(): array
    {
        $updates = $this->users->getUpdateItems();
        $updates = array_merge($updates, $this->securities->getUpdateItems());
        $updates = array_merge($updates, $this->plans->getUpdateItems());

        return array_map(function ($item) {
            return [
                'PutRequest' => [
                    'Item' => $item
                ]
            ];
        }, $updates);
    }
}