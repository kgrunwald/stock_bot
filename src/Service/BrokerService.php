<?php

namespace App\Service;

use App\Entity\Goal;
use App\Entity\Holding;
use App\Entity\Order;
use App\Entity\Security;
use App\Repository\SecretRepository;
use App\Security\SecurityService;
use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BrokerService
{
    private SecurityService $security;
    private SecretRepository $secrets;
    private HttpClientInterface $client;

    public function __construct(SecurityService $security, SecretRepository $secrets, HttpClientInterface $client)
    {
        $this->securityService = $security;
        $this->secrets = $secrets;
        $this->client = $client;
    }

    public function getCurrentBid(Security $security)
    {
        return $this->getLastQuote($security)['bidprice'];
    }

    public function getCurrentAsk(Security $security)
    {
        return $this->getLastQuote($security)['askprice'];
    }

    public function getLastQuote(Security $security)
    {
        $res = $this->sendRequest('GET', '/v1/last_quote/stocks/'.$security->getSymbol());
        return $res['last'];
    }

    public function getPosition(Holding $holding)
    {
        return $this->sendRequest('GET', '/v2/positions/'.$holding->getSecurity()->getSymbol());
    }

    public function submitLimitOrder(Order $order): array
    {
        $body = [
            'symbol' => $order->getSecurity()->getSymbol(),
            'qty' => $order->getQty(),
            'side' => $order->getSide(),
            'type' => 'limit',
            'time_in_force' => 'gtc',
            'limit_price' => $order->getLimitPrice()
        ];

        return $this->sendRequest('POST', '/v2/orders', $body);
    }

    public function getOrderDetails(Order $order)
    {
        return $this->sendRequest('GET', '/v2/orders/'.$order->getExternalId());
    }

    public function sendRequest(string $method, string $url, ?array $body = null)
    {
        $user = $this->security->getUser();
        $creds = $this->secrets->getCredentials($user);

        $res = $this->client->request($method, $url, [
            'headers' => [
                'APCA-API-KEY-ID' => $creds->getToken(),
                'APCA-API-SECRET-KEY' => $creds->getSecret()
            ],
            'json' => $body
        ]);

        if($res->getStatusCode() !== 200) {
            throw new Exception('Error interacting with Alpaca: ' . $res->getContent(false), 500);
        }

        return $res->toArray();
    }
}