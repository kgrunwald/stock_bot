<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class Oauth2Controller extends AbstractController
{
    public function redirectToAuthentication(): Response
    {
        $url = 'https://jk-invest.auth.us-west-2.amazoncognito.com/oauth2/authorize?'.http_build_query(array(
            'response_type' => 'code',
            'client_id' => $_ENV['COGNITO_CLIENT_ID'],
            'redirect_uri' => $_ENV['COGNITO_REDIRECT_URI'],
            'scope' => 'openid profile email'
        ));
    
        return $this->json(['url' => $url]);
    }

    public function callback(Request $request): Response
    {
       
    }

    
}
