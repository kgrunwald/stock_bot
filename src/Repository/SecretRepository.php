<?php

namespace App\Repository;

use App\Entity\User;
use Aws\Ssm\Exception\SsmException;
use Aws\Ssm\SsmClient;
use GuzzleHttp\Promise\Promise;
use Psr\Log\LoggerInterface;

class SecretRepository
{
    private SsmClient $ssmClient;
    private LoggerInterface $logger;

    const KEY_NAME_TOKEN = '/token';

    public function __construct(SsmClient $client, LoggerInterface $logger)
    {
        $this->ssmClient = $client;    
        $this->logger = $logger;
    }

    public function addToken(User $user, string $token): Promise
    {
        return $this->ssmClient->putParameterAsync([
            'Name' => $this->getKeyName($user, self::KEY_NAME_TOKEN),
            'Type' => 'SecureString',
            'Value' => $token
        ]);
    }

    public function getToken(User $user): Promise
    {
        return $this->client->getParameterAsync([
            'Name' => $this->getKeyName($user, self::KEY_NAME_TOKEN),
            'WithDecryption' => true,
        ])->then(function($result) {
            return $result['Parameter']['Value'];
        }, function($reason) {
            $this->logger->error('Error getting user token from SSM', ['e' => $reason]);
            return null;
        });
    }

    private function getKeyName(User $user, string $type) {
        return '/jk-stockbot/' . $user->getId() . $type;
    }
}
