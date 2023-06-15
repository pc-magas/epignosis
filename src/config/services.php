<?php

use App\Application;
use App\Controllers\HomepageController;
use Psr\Container\ContainerInterface;

return [
    // ############## Core Services #################
    'db' => function(ContainerInterface $int){
       $config = require_once(Application::CONFIG_PATH.'/db.php');
       return Application::createDB($config);
    },
    'session' => function(ContainerInterface $int){

        $sessionOptions = require_once(Application::CONFIG_PATH.'/session.php');
        $config = new  Laminas\Session\Config\StandardConfig();
        $config->setOptions($sessionOptions);
 
        $manager = new Laminas\Session\SessionManager($config);
        $manager->setStorage(new Laminas\Session\Storage\SessionStorage());
        Laminas\Session\Container::setDefaultManager($manager);

        return new Laminas\Session\Container('session');
    },
    'twig' => function(ContainerInterface $int){
        $loader = new \Twig\Loader\FilesystemLoader(Application::VIEWS_DIR);
        return new \Twig\Environment($loader);
    },
    'mail' => function(ContainerInterface $int){

        $mail_config = require_once Application::CONFIG_PATH.'/mail.php';

        $dsn = 'smtp://';

        // Urlencode is specified at mail docs
        // https://symfony.com/doc/current/mailer.html
        if(!empty($mail_config['username'])){
            $dsn.=urldecode($mail_config['username']);
        }

        if(!empty($mail_config['password'])){
            $dsn.=':'.urldecode($mail_config['password']);
        }

        $mail_config['host'] = (trim($mail_config['host'])??'localhost');
        $dsn.="@".(trim($mail_config['host'])??'localhost');
        
        $dsn.=":".($mail_config['port']??25);

        $transport = Symfony\Component\Mailer\Transport::fromDsn($dsn);
        return  new Symfony\Component\Mailer\Mailer($transport);
    },
    // ################### Custom Services ######################### 
    \App\Services\UserService::class => function(ContainerInterface $int){
        $db = $int->get('db');
        $mail = $int->get('mail');

        return new \App\Services\UserService($db,$mail);
    },

    // ################### Controllers ######################### 
    \App\Controllers\HomepageController::class => function(ContainerInterface $int){
        return new \App\Controllers\HomepageController($int);   
    },
    \App\Controllers\UserController::class => function(ContainerInterface $int){
        return new \App\Controllers\UserController($int);
    }
];