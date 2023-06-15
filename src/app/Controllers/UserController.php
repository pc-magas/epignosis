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

        if(!$this->logedinAsManager()){
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

        if(!$this->logedinAsManager() || !$this->validateCSRF($_POST['csrf']) ){
            $this->jsonResponse(['msg'=>'User is Not Authorized To perform this Action'],403);
            return;
        }

        $userService = $di->get(UserService::class);
        try {
            
            if($userService->registerUser($_POST['email'],$_POST['password'],$_POST['fullname'],$_POST['role'])){
                $this->jsonResponse(['msg'=>'User sucessfully has registered. An email is sent to '.$_POST['email']],200);
            } else {
                $this->jsonResponse(['msg'=>'Registration failed'],500);
            }
        } catch(\InvalidArgumentException $e) {

            $field = '';
            $type = 'empty';

            switch($e->getCode()){
                case UserService::INVALID_ROLE_ERROR:
                    $field = 'role';
                    $type = 'invalid';
                    break;
                case UserService::INVALID_EMAIL_ERROR:
                    $field = 'email';
                    $type = 'invalid';
                    break;
                case UserService::EMPTY_PASSWORD_ERROR:
                    $fiels = 'password';
                    $type = 'empty';
                    break;
            }

            $this->jsonResponse(['field'=>$field,'type'=>$type],400);
            return;
        } catch(\App\Exceptions\UserAlreadyExistsException $e){
            $this->jsonResponse(['email'=>$_POST['email']],409);
            return ;
        } catch(\Exception $e) {
            $this->jsonResponse(['msg'=>'Registration failled'],500);
            return;
        }
    }

    
}