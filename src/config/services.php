<?php

use App\Application;
use Psr\Container\ContainerInterface;
use function DI\factory;

return [
    'db' => function(ContainerInterface $int){
       $config = require_once(Application::CONFIG_PATH.'/phinx.php');
       return Application::createDB($config);
    },
];