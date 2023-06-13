<?php

use App\Application;
use Psr\Container\ContainerInterface;
use function DI\factory;

return [
    'db' => function(ContainerInterface $int){
       $config = require_once(Application::CONFIG_PATH.'/phinx.php');
       return Application::createDB($config);
    },
    'session' => function(ContainerInterface $int){

        $sessionOptions = require_once(Application::CONFIG_PATH.'/session.php');
        $config = new  Laminas\Session\Config\StandardConfig();
        $config->setOptions($sessionOptions);
        return new Laminas\Session\SessionManager($config);
    },
    'twig' => function(ContainerInterface $int){
        $loader = new \Twig\Loader\FilesystemLoader(Application::VIEWS_DIR);
        return new \Twig\Environment($loader, [
            'cache' => Application::VIEW_CACHE_DIR,
        ]);
    }
    // Edit bellow this line
];