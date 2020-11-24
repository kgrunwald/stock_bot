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

class AmazonAuthenticator extends AbstractGuardAuthenticator
{
    private UserRepository $userRepo;
    private EntityManagerInterface $em;
    private HttpClientInterface $client;
    private RouterInterface $router;

    public function __construct(UserRepository $userRepo, EntityManagerInterface $em, HttpClientInterface $client, RouterInterface $router)
    {
        $this->userRepo = $userRepo;
        $this->em = $em;
        $this->client = $client;
        $this->router = $router;
    }

    public function supports(Request $request)
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'auth_callback';
    }

    public function getCredentials(Request $request)
    {
        $code = $request->get('code');
        $token = $this->getAccessToken($code);
        return $token;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null === $credentials) {
            return null;
        }

        $info = $this->getUserInfo($credentials);
        $username = $info['username'];
        
        $user = $this->userRepo->findOneBy(['username' => $username]);
        if (!$user) {
            $user = new User();
            $user->setUsername($username);
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
        $targetUrl = $this->router->generate('home');
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

    private function getAccessToken(string $code) {
        $res = $this->client->request('POST', 'https://jk-invest.auth.us-west-2.amazoncognito.com/oauth2/token', [
            'headers' => [
                'Authorization' => 'Basic '.base64_encode($_ENV['COGNITO_CLIENT_ID'] . ":" . $_ENV['COGNITO_CLIENT_SECRET']),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => $_ENV['COGNITO_CLIENT_ID'],
                'redirect_uri' => $_ENV['COGNITO_REDIRECT_URI'],
                'code' => $code,
            ]
        ]);

        return $res->toArray()['access_token'];
    }

    private function getUserInfo(string $token) {
        $res = $this->client->request('GET', 'https://jk-invest.auth.us-west-2.amazoncognito.com/oauth2/userInfo', [
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ]
        ]);

        return $res->toArray();
    }
}