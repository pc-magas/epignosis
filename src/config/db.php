<?php

/**
 * We use it for migrations Only 
 */
return [
    'host' =>  $_ENV['DB_HOST'],
    'name' => $_ENV['DB_NAME'],
    'user' => $_ENV['DB_USER'],
    'pass' => $_ENV['DB_PASSWD'],
    'port' => $_ENV['DB_PORT']??'3306',
    'charset' => 'utf8',
];
