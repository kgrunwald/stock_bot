<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\DbContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AlpacaAuthenticator extends AbstractGuardAuthenticator
{
    private DbContext $dbContext;
    private HttpClientInterface $client;
    private RouterInterface $router;
    private LoggerInterface $logger;
    private string $secret;

    public function __construct(string $secret, DbContext $dbContext, HttpClientInterface $client, RouterInterface $router, LoggerInterface $logger)
    {
        $this->dbContext = $dbContext;
        $this->client = $client;
        $this->router = $router;
        $this->logger = $logger;
        $this->secret = $secret;
    }

    public function supports(Request $request)
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'auth_callback';
    }

    public function getCredentials(Request $request)
    {
        $code = $request->get('code');
        return $this->getAccessToken($code);
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null === $credentials) {
            return null;
        }

        $info = $this->getUserInfo($credentials);
        $accountId = $info['id'];

        $user = $this->dbContext->users->getByAccountId($accountId);
        if (!$user) {
            $user = new User();
            $user->setId($accountId);
            $this->dbContext->users->add($user);
            $this->dbContext->commit();
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $user = $token->getUser();
        if (strpos($user->getUsername(), '@') > 0) {
            $targetUrl = $this->router->generate('home', ['reactRouting' => 'account']);
            return new RedirectResponse($targetUrl);
        }

        $targetUrl = $this->router->generate('home', ['reactRouting' => 'register']);
        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            // you might translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }

    private function getAccessToken(string $code)
    {
        $res = $this->client->request('POST', $_ENV['ALPACA_OAUTH_TOKEN_DOMAIN'] . '/oauth/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $_ENV['ALPACA_OAUTH_CLIENT_ID'],
                'client_secret' => $this->secret,
                'redirect_uri' => $_ENV['ALPACA_OAUTH_REDIRECT_URI'],
            ]
        ]);

        if ($res->getStatusCode() !== 200) {
            $this->logger->error("Failed getting access token", ['err' => $res->toArray()]);
            return null;
        }

        return $res->toArray()['access_token'];
    }

    private function getUserInfo(string $token)
    {
        $res = $this->client->request('GET', $_ENV['ALPACA_DOMAIN'] . '/v2/account', [
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ]
        ]);

        return $res->toArray();
    }
}
