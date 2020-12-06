<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class Oauth2Controller extends AbstractController
{
    public function redirectToAuthentication(): Response
    {
        $url = $_ENV['ALPACA_OAUTH_AUTH_DOMAIN'] . '/oauth/authorize?'.http_build_query(array(
            'response_type' => 'code',
            'client_id' => $_ENV['ALPACA_OAUTH_CLIENT_ID'],
            'redirect_uri' => $_ENV['ALPACA_OAUTH_REDIRECT_URI'],
            'scope' => 'data account:write'
        ));
    
        return $this->json(['url' => $url]);
    }

    public function callback()
    {
       
    }

    
}
