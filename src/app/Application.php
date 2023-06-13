<?php

namespace App;

use PDO;

class Application
{
    /**
     * Basic configuration Path
     * @const CONFIG_PATH
     */
    const CONFIG_PATH = __DIR__.'/../config';

    const RESOURCES_DIR = __DIR__.'/../resources';

    const VIEWS_DIR = self::RESOURCES_DIR.'/views';

    const STORAGE_DIR = __DIR__.'/../storage/';
    const CACHE_DIR = self::STORAGE_DIR.'/cache';
    const VIEW_CACHE_DIR = self::CACHE_DIR.'/views';

    /**
     * 
     * @property \Bramus\Router\Router
     */
    private $router;

    private $di;

    public function __construct()
    {
        $this->router = new \Bramus\Router\Router();
        $this->di = $this->diBuild();
    }

    /**
     * Generic function for creating a database
     *
     * @param array $config Containing the values:
     * 
     * @return PDO
     * @throws Exception
     */
    public static function createDB(array $config)
    {
        $connectionString = 'mysql:host=%s;port=%s;dbname=%s';
        
        $env = $_ENV['APP_ENV']??'development';
        $config = $config['environments'][$env];

        $dbPort = !empty($config['port'])?$config['port']:3306;

        $connectionString = sprintf($connectionString,$config['host'],$dbPort,$config['name']);
        return new PDO($connectionString,$config['user'],$config['pass']);
    }

    /**
     * Setup Dependency Injection
     *
     */
    private function diBuild()
    {
        $containerBuilder = new \DI\ContainerBuilder();
        $definitions = 
        $containerBuilder->addDefinitions(self::CONFIG_PATH.'/services.php');
        return $containerBuilder->build();
    }

    /**
     * Manual routing configuration
     */
    private function confiGureRoutes()
    {
        $di = $this->di;
        $this->router->get('/',function() use ($di) {
            \App\Controllers\BaseController::hello($di);
        });

    }

    /**
     * Bootstrap and run the application
     *
     * @return void
     * @throws Exception
     */
    public function run()
    {
        $this->confiGureRoutes();
        $this->router->run();
    }
}