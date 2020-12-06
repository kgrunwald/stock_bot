<?php

namespace App\DependencyInjection;

use Aws\Ssm\SsmClient;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class SSMEnvVarProcessor implements EnvVarProcessorInterface
{
    private SsmClient $client;

    public function __construct(SsmClient $client) {
        $this->client = $client;
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv)
    {
        $secretName = $getEnv($name);

        $result = $this->client->getParameter([
            'Name' => $secretName,
            'WithDecryption' => true,
        ]);

        return $result['Parameter']['Value'];
    }

    public static function getProvidedTypes()
    {
        return [
            'ssm' => 'string',
        ];
    }
}