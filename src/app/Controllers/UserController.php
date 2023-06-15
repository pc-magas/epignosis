<?php

namespace App\Controllers;

use App\Exceptions\LinkHasExpired;
use App\Exceptions\LinkHasExpiredException;
use App\Services\UserService;
use App\Utils\Generic;

class UserController extends \App\Controllers\BaseController
{

    public function login()
    {
        $di=$this->getServiceContainer();
        $twig = $di->get('twig');

        echo $twig->render('login.html.twig',[
            'url'=>'/login',
            'csrf_token' => Generic::csrf($di->get('session'))
        ]);
    }

    public function loginViaHttpPost()
    {
        $di=$this->getServiceContainer();
        $session = $di->get('session');
        $twig = $di->get('twig');

        if(!empty($session->user)){
            header('Location: '.Generic::getAppUrl(''));
        }
    
        if($this->validateCSRF($_POST['csrf_token'])){
            http_response_code(403);
            echo $twig->render('login.html.twig',[
                'url'=>'/login',
                'csrf_token' => $this->getCsrfToken(),
                'error'=> 'Internal Error'
            ]);
        }

        /**
         * @var UserService
         */
        $userService = $di->get(UserService::class);

        try {
          $userInfo =  $userService->login($_POST['email'],$_POST['pass']);
         
          $session->user = $userInfo;
          header('Location: '.Generic::getAppUrl(''));
          return;
        }catch(\Exception $e){

            http_response_code(403);
            echo $twig->render('login.html.twig',[
                'url'=>'/login',
                'csrf_token' => $this->getCsrfToken(),
                'error'=> $e->getMessage()
            ]);
        }
    }

    public function logout($di){
        $di = $this->getServiceContainer();

        $session = $di->get('session');
        $session->user = null;
        header('Location: '.Generic::getAppUrl(''));
    }

    public function activate($token)
    {
        $di = $this->getServiceContainer();
        
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

    public function registerUser()
    {
        $di = $this->getServiceContainer();
        $session = $di->get('session');

        if($this->logedinAsManager()){
            http_response_code(403);
            header('Location: '.Generic::getAppUrl(''));
        }

        $csrfToken = $this->getCsrfToken();

        $twig = $di->get('twig');
        echo $twig->render('modify_user.html.twig',[
            'csrf'=>$csrfToken,
            'title'=>'User Registration',
            'action'=>Generic::getAppUrl('/user/add')
        ]);
    }

    public function registerAction()
    {
        $di = $this->getServiceContainer();
        $session = $di->get('session');

        if(!$this->logedinAsManager()){
            http_response_code(403);
            echo json_encode(['msg'=>'User is Not Authorized To perform this Action']);
        }

        if($this->validateCSRF($_POST['csrf'])){
            http_response_code(403);
            echo json_encode(['msg'=>'User is Not Authorized To perform this Action']);
        }

    }
}