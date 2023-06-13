<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Utils\Generic;

class UserController
{
    public static function login($di)
    {
        $twig = $di->get('twig');

        echo $twig->render('login.html.twig',[
            'url'=>'/login',
            'csrf_token' => Generic::csrf($di->get('session'))
        ]);
    }

    public static function loginViaHttpPost($di)
    {
        $session = $di->get('session');
        $twig = $di->get('twig');

        /**
         * @var UserService
         */
        $userService = $di->get(UserService::class);

        $token = $_POST['csrf_token'];
        if($token!=$session->csrf){

            http_response_code(403);
            echo $twig->render('login.html.twig',[
                'url'=>'/login',
                'csrf_token' => Generic::csrf($session),
                'error'=> 'Internal Error'
            ]);
        }

        try {
          $userInfo =  $userService->login($_POST['email'],$_POST['pass']);
         
          $session->user = $userInfo;
          header('Location: '.Generic::getAppUrl(''));
          return;
        }catch(\Exception $e){

            http_response_code(403);
            echo $twig->render('login.html.twig',[
                'url'=>'/login',
                'csrf_token' => Generic::csrf($di->get('session')),
                'error'=> $e->getMessage()
            ]);
        }
    }

    public static function logout($di){
        $session = $di->get('session');
        $session->user = null;
        header('Location: '.Generic::getAppUrl(''));
    }
}