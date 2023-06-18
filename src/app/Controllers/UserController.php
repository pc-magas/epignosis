<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Utils\Generic;

class UserController extends \App\Controllers\BaseController
{

    private function handleInvalidArgumentException(\InvalidArgumentException $e):void
    {   

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

    }
    
    public function login()
    {
        $di=$this->getServiceContainer();
        $twig = $di->get('twig');

        echo $twig->render('login.html.twig',[
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function loginViaHttpPost()
    {
        $di=$this->getServiceContainer();
        $session = $di->get('session');
        $twig = $di->get('twig');       
    
        if(!empty($session->user)){
            header('Location: '.Generic::getAppUrl(''));
            return;
        }

        if(!$this->validateCSRF($_POST['csrf_token'])){
            http_response_code(403);
            echo $twig->render('login.html.twig',[
                'url'=>'/login',
                'csrf_token' => $this->getCsrfToken(),
                'error'=> 'Internal Error'
            ]);
            return;
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
            return;
        }
    }

    public function logout(){
        $di = $this->getServiceContainer();

        $session = $di->get('session');
        $session->user = null;
        header('Location: '.Generic::getAppUrl(''));
    }
    
    public function registerUser()
    {
        $di = $this->getServiceContainer();

        if(!$this->logedinAsManager()){
            http_response_code(403);
            header('Location: '.Generic::getAppUrl(''));
        }

        $csrfToken = $this->getCsrfToken();

        $twig = $di->get('twig');
        echo $twig->render('new_user.html.twig',[
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
            $this->handleInvalidArgumentException($e);
            return;
        } catch(\App\Exceptions\UserAlreadyExistsException $e){
            $this->jsonResponse(['email'=>$_POST['email']],409);
            return ;
        } catch(\Exception $e) {
            $this->jsonResponse(['msg'=>'Registration failled'],500);
            return;
        }
    }

    public function deleteUser($user_id)
    {
        $di = $this->getServiceContainer();

        if(!$this->logedinAsManager() || !$this->validateCSRF($_POST['csrf']) ){
            $this->jsonResponse(['msg'=>'User is Not Authorized To perform this Action'],403);
            return;
        }

        /**
         * @var UserService
         */
        $userService = $di->get(UserService::class);
        if(!$userService->deleteUser($user_id)){
            $this->jsonResponse(['msg'=>'User cannot be deleted'],500);
            return;
        }

        $this->jsonResponse(['msg'=>'Delete Success'],200);
    }

    public function updateUserPage($user_id)
    {
        $di = $this->getServiceContainer();

        if(!$this->logedinAsManager() && !$this->logedinAsUser($user_id)){
            http_response_code(403);
            header('Location: '.Generic::getAppUrl(''));
            return;
        }

        /**
         * @var UserService
         */
        $userService = $di->get(UserService::class);

        $user = $userService->getUserInfo($user_id);

        if(empty($user_id)){
            http_response_code(404);
            echo "USER NOT FOUND";
            return;
        }

        $twig = $di->get('twig');
        $session = $di->get('session');
        echo $twig->render('edit_user.html.twig',[
            'csrf'=>$this->getCsrfToken(),
            'user'=>$user,
            'role'=>$session->user['role']
        ]);
    }

    public function updateUser($user_id)
    {
        $di = $this->getServiceContainer();
        
        if(!$this->logedinAsManager() && !$this->logedinAsUser($user_id) && !$this->validateCSRF($_POST['csrf'])){
            $this->jsonResponse(['msg'=>'User is Not Authorized To perform this Action'],403);
            return;
        }

        /**
         * @var UserService
         */
        $userService = $di->get(UserService::class);

        try {
            
            if(!$userService->modifyEmailAndName($user_id,$_POST['email'],$_POST['name'])){
                $this->jsonResponse(['msg'=>'User cannot be updated'],500);
                return;
            }

            $this->jsonResponse(['msg'=>'User sucessfully has been updated'],200);
            return;
        }catch(\InvalidArgumentException $e) {
            $this->handleInvalidArgumentException($e);
            return;
        }
    }

    public function updatePassword($user_id)
    {
        $di = $this->getServiceContainer();
        
        if(!$this->logedinAsManager() && !$this->logedinAsUser($user_id) && !$this->validateCSRF($_POST['csrf'])){
            $this->jsonResponse(['msg'=>'User is Not Authorized To perform this Action'],403);
            return;
        }

        /**
         * @var UserService
         */
        $userService = $di->get(UserService::class);

        try {
            
            if(!$userService->updatePassword($user_id,$_POST['password'])){
                $this->jsonResponse(['msg'=>'User\'s password cannot be updated'],500);
                return;
            }

            $this->jsonResponse(['msg'=>'User\'s password sucessfylly has been updated'],200);
            return;
        }catch(\InvalidArgumentException $e) {
            return;
        }
    }

    
    public function listUsers()
    {
        $di = $this->getServiceContainer();
        
        if(!$this->logedinAsManager()){
            $this->jsonResponse(['msg'=>'User is Not Authorized To perform this Action'],403);
            return;
        }

        $page = empty($_GET['page'])?1:(int)$_GET['page'];
        $limit = empty($_GET['limit'])?10:(int)$_GET['limit'];

        /**
         * @var UserService
         */
        $service = $di->get(UserService::class);
        $results = $service->listUsers($page,$limit,$pages);

        $twig = $di->get('twig');
        echo $twig->render('list_users.html.twig',[
            'csrf'=>$this->getCsrfToken(),
            'users'=>$results,
            'pages'=>(int)$pages,
            'current_page'=>$page, //XSS prevention
            'last_page'=>(int)$pages,
            'limit'=>(int)$limit //XSS prevention
        ]);
    }

    public function resetPasswordRequest()
    {
        if(empty($_POST['csrf_token'])){
            $this->jsonResponse(['msg'=>'User is Not Authorized To perform this Action'],403);
        }

        $csrfToken = $this->getCsrfToken();

        if($csrfToken!=trim($_POST['csrf_token'])){
            $this->jsonResponse(['msg'=>'User is Not Authorized To perform this Action'],403);
        }

        $service = $this->getServiceContainer()->get(UserService::class);

        try{
            $service->sendResetPasswordEmail($_POST['email']);
            $this->jsonResponse(['msg'=>'Emailfor reset password is sent to '.strip_tags($_POST['email'])],200);
        } catch(\InvalidArgumentException $e){ // Service Validates Email
            $this->jsonResponse(['msg'=>'Email Is not valid'],400);
        }
    }
    
}