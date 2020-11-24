<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;

class AmazonAuthenticator extends AbstractGuardAuthenticator
{
    private UserRepository $userRepo;
    private EntityManagerInterface $em;
    private HttpClientInterface $client;
    private RouterInterface $router;
    private LoggerInterface $logger;

    public function __construct(UserRepository $userRepo, EntityManagerInterface $em, HttpClientInterface $client, RouterInterface $router, LoggerInterface $logger)
    {
        $this->userRepo = $userRepo;
        $this->em = $em;
        $this->client = $client;
        $this->router = $router;
        $this->logger = $logger;
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
        $email = $info['email'];

        $user = $this->userRepo->findOneBy(['email' => $email]);
        if (!$user) {
            $user = new User();
            $user->setName($info['name']);
            $user->setEmail($info['email']);
            $user->setSubId($info['sub']);
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $targetUrl = $this->router->generate('home', ['reactRouting' => 'account']);
        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
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
        $res = $this->client->request('POST', $_ENV['COGNITO_AUTH_DOMAIN'] . '/oauth2/token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($_ENV['COGNITO_CLIENT_ID'] . ":" . $_ENV['COGNITO_CLIENT_SECRET']),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => $_ENV['COGNITO_CLIENT_ID'],
                'redirect_uri' => $_ENV['COGNITO_REDIRECT_URI'],
                'code' => $code,
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
        $res = $this->client->request('GET', $_ENV['COGNITO_AUTH_DOMAIN'] . '/oauth2/userInfo', [
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ]
        ]);

        return $res->toArray();
    }
}
