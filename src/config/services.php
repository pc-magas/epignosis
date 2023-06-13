<?php

use Psr\Container\ContainerInterface;
use function DI\factory;

return [
    'db' => function(ContainerInterface $int){
        $config = require_once(__DIR__.'/phinx.php');
        \App\Application::createDB($config);
    },
];