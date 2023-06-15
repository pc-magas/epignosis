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


    public function getServiceContainer()
    {
        return $this->di;
    }

    public function validateCSRF(string $token){
        $csrf = Generic::csrf($this->getServiceContainer()->get('session'));
        
        return $csrf === $token;
    }
}