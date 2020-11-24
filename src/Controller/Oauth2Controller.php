<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class Oauth2Controller extends AbstractController
{
    public function redirectToAuthentication(): Response
    {
        $url = $_ENV['COGNITO_AUTH_DOMAIN'] . '/oauth2/authorize?'.http_build_query(array(
            'response_type' => 'code',
            'client_id' => $_ENV['COGNITO_CLIENT_ID'],
            'redirect_uri' => $_ENV['COGNITO_REDIRECT_URI'],
            'scope' => 'openid profile email'
        ));
    
        return $this->json(['url' => $url]);
    }

    public function callback()
    {
       
    }

    
}
