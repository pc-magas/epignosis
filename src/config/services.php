<?php

use App\Application;
use Psr\Container\ContainerInterface;

return [
    // ############## Core Srvices #################
    'db' => function(ContainerInterface $int){
       $config = require_once(Application::CONFIG_PATH.'/phinx.php');
       return Application::createDB($config);
    },
    'session' => function(ContainerInterface $int){

        $sessionOptions = require_once(Application::CONFIG_PATH.'/session.php');
        $config = new  Laminas\Session\Config\StandardConfig();
        $config->setOptions($sessionOptions);
 
        $manager = new Laminas\Session\SessionManager($config);
        Laminas\Session\Container::setDefaultManager($manager);

        return new  Laminas\Session\Container('session');
    },
    'twig' => function(ContainerInterface $int){
        $loader = new \Twig\Loader\FilesystemLoader(Application::VIEWS_DIR);
        return new \Twig\Environment($loader, [
            'cache' => Application::VIEW_CACHE_DIR,
        ]);
    },
    // ################### Custom Services ######################### 
    \App\Services\UserService::class => function(ContainerInterface $int){
        $db = $int->get('db');
        $session = $int->get('session');

        return new \App\Services\UserService($db,$session);
    }
];