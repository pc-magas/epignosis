<?php

namespace App\Controllers;

use App\Exceptions\LinkHasExpired;
use App\Exceptions\LinkHasExpiredException;
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

        if(!empty($session->user)){
            header('Location: '.Generic::getAppUrl(''));
        }

        /**
         * @var UserService
         */
        $userService = $di->get(UserService::class);

        $token = $_POST['csrf_token'];
        if($token!=$session->csrf) {
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

    public static function activate($di,$token)
    {
        /**
         * @var UserService
         */
        $userService = $di->get(UserService::class);

        if(!$userService->activate($token)){
            http_response_code(404);
            $twig = $di->get('twig');
            echo $twig->render('404.html.twig');
            return;
        }

        header('Location: '.Generic::getAppUrl('/login'));
    }

    public static function registerUser($di)
    {
        $session = $di->get('session');

        if(empty($session->user)){
            http_response_code(403);
            echo json_encode(['msg'=>'User is Not Logged In']);
        }

        if(empty($session->user['role'] != 'Manager')){
            http_response_code(403);
            echo json_encode(['msg'=>'User is Not Authorized To perform this Action']);
        }

        $csrfToken = Generic::csrf($session);

        $twig = $di->get('twig');
        echo $twig->render('modify_user.html.twig',[
            'csrf'=>$csrfToken,
            'title'=>'User Registration',
            'action'=>Generic::getAppUrl('/register')
        ]);
    }

    public static function registerAction($di)
    {
        $session = $di->get('session');

        if(empty($session->user)){
            http_response_code(403);
            echo json_encode(['msg'=>'User is Not Logged In']);
        }

        if(empty($session->user['role'] != 'Manager')){
            http_response_code(403);
            echo json_encode(['msg'=>'User is Not Authorized To perform this Action']);
        }



    }
}