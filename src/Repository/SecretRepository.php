<?php

namespace App\Repository;

use App\Entity\User;
use App\Security\Credentials;
use Aws\Ssm\SsmClient;
use GuzzleHttp\Promise\Promise;
use Symfony\Component\Serializer\SerializerInterface;

class SecretRepository
{
    private SsmClient $ssmClient;
    private SerializerInterface $serializer;

    const KEY_NAME_TOKEN = '/token';

    public function __construct(SsmClient $client, SerializerInterface $serializer)
    {
        $this->ssmClient = $client;
        $this->serializer = $serializer;
    }

    public function setCredentials(User $user, Credentials $creds): Promise
    {
        return $this->ssmClient->putParameterAsync([
            'Name' => $this->getKeyName($user, self::KEY_NAME_TOKEN),
            'Type' => 'SecureString',
            'Value' => $this->serializer->serialize($creds, 'json')
        ]);
    }

    public function getCredentials(User $user): Credentials
    {
        $result = $this->client->getParameter([
            'Name' => $this->getKeyName($user, self::KEY_NAME_TOKEN),
            'WithDecryption' => true,
        ]);

        $json = $result['Parameter']['Value'];
        return $this->serializer->deserialize($json, Credentials::class, 'json');
    }

    private function getKeyName(User $user, string $type)
    {
        return '/jk-stockbot/' . $user->getId() . $type;
    }
}
