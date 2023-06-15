<?php

namespace App\Controllers;

use App\Utils\Generic;

class BaseController
{
    /**
     * Dependency Injection Container
     *
     * @var \Psr\Container\ContainerInterface
     */
    private $di;

    public function __construct(\Psr\Container\ContainerInterface $container){
        $this->di = $container;
    }


    /**
     * Gewt Container Service
     *
     * @return \Psr\Container\ContainerInterface
     */
    public function getServiceContainer(): \Psr\Container\ContainerInterface
    {
        return $this->di;
    }


    /**
     * Validate Csrf Token
     *
     * @param string $token
     * @return bool
     */
    public function validateCSRF(string $token):bool
    {
        $csrf = $this->getCsrfToken();
        
        return $csrf === $token;
    }

    
    /**
     * Retrieve CSRF token
     *
     * @return void
     */
    public function getCsrfToken():string
    {
        $session = $this->getServiceContainer()->get('session');

        if(empty($session->csrf)){
            $token = base64_encode(random_bytes(100));
            $token = str_replace('=','',$token);
            $token = substr($token,0,50);
            $session->csrf = $token;
            
        } else {
            $token = $session->csrf;
        }

        return $token;
    }

    /**
     * Check if user is loggedin as manager
     *
     * @return bool
     */
    public function logedinAsManager():bool
    {
        $session = $this->getServiceContainer()->get('session');
        return empty($session->user) || (!empty($session->user['role']) && $session->user['role'] != 'MANAGER');
    }
}