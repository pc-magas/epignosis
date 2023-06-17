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
     * @var \Bramus\Router\Router
     */
    private $router;

    /**
     * @var \DI\Container
     */
    private $di;

    private $console;

    public function __construct(bool $console = false)
    {
        $this->console = $console;
        $this->router = new \Bramus\Router\Router();
        $this->di = $this->diBuild();
    }

    public static function baseUrl()
    {
        $config = self::CONFIG_PATH.'/app.php';
        $config = require_once($config);
        return $config['url'];
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
        
        $dbPort = !empty($config['port'])?$config['port']:3306;

        $connectionString = sprintf($connectionString,$config['host'],$dbPort,$config['name']);
        
        $pdo = new PDO($connectionString,$config['user'],$config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    protected function getDi()
    {
        return $this->di;
    }

    /**
     * Setup Dependency Injection Container
     */
    private function diBuild()
    {
        $containerBuilder = new \DI\ContainerBuilder();
        $containerBuilder->addDefinitions(self::CONFIG_PATH.'/services.php');
        return $containerBuilder->build();
    }

    /**
     * Manual routing configuration
     */
    private function configureRoutes()
    {
        $di = $this->di;
        $this->router->get('/',function() use ($di) {
            $di->get(\App\Controllers\HomepageController::class)->homepage();
        });

        $this->router->get('/user/a/{token}',function($token) use ($di) {
            $di->get(\App\Controllers\UserController::class)->activate($token);
        });

        $this->router->get('/login',function() use ($di) {
            $di->get(\App\Controllers\UserController::class)->login();
        });

        $this->router->post('/login',function() use ($di) {
            $di->get(\App\Controllers\UserController::class)->loginViaHttpPost();
        });

        $this->router->all('/logout',function() use ($di) {
            $di->get(\App\Controllers\UserController::class)->logout($di);
        });

        $this->router->get('/user/add',function() use ($di){
            $di->get(\App\Controllers\UserController::class)->registerUser();
        });

        $this->router->post('/user/add',function() use ($di){
            $di->get(\App\Controllers\UserController::class)->registerAction();
        });

        $this->router->post('/user/delete/{user_id}',function($user_id) use ($di){
            $di->get(\App\Controllers\UserController::class)->deleteUser($user_id);
        });

        $this->router->post('/user/password/{user_id}',function($user_id) use ($di){
            $di->get(\App\Controllers\UserController::class)->updatePassword($user_id);
        });

        $this->router->post('/user/update/{user_id}',function($user_id) use ($di){
            $di->get(\App\Controllers\UserController::class)->updateUser($user_id);
        });

        $this->router->get('/users',function() use ($di){
            $di->get(\App\Controllers\UserController::class)->listUsers();
        });

        $this->router->get('/vaccation/add',function() use ($di){
            $di->get(\App\Controllers\VaccationController::class)->addVaccationPage();
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
        if(!$this->console){
            $this->configureRoutes();
            $this->router->run();
            return;
        }
    }
}