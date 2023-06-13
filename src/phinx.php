<?php

/**
 * We use it for migrations Only 
 * Run at Local Environment
 */
return
[
    'paths' => [
        'migrations' => __DIR__.'/db/migrations',
        'seeds' => __DIR__.'/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' =>  'db',
            'name' => 'test',
            'user' => 'test_usr',
            'pass' => 'test_passwd',
            'port' => '3306',
            'charset' => 'utf8',
        ],
    ],
    'version_order' => 'creation'
];
