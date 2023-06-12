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

    /**
     * Constant used at router in order to indicate an HTTP Post
     */
    const HTTP_GET='GET';

    /**
     * Http POST method constant used at router
     */
    const HTTP_POST='POST';
    const HTTP_PUT='PUT';
    const HTTP_PATCH='PATCH';
    const HTTP_OPTIONS='OPTIONS';
    const HTTP_DELETE='DELETE';
    const HTTP_ANY='all';


    public static function createDB()
    {
        $config = require_once(self::CONFIG_PATH.'/database.php');
        $connectionString = 'mysql:host=%s;port=%s;dbname=%s';
        
        $dbPort = !empty($config['db_port'])?$config['db_port']:3306;

        $connectionString = sprintf($connectionString,$config['db_host'],$dbPort,$config['db_name']);
        return new PDO($connectionString,$config['db_user'],$config['db_passwd']);
    }

    public function di()
    {
        // Db is required
        $db = self::createDB();

    }

    public function routes()
    {
        $router = new \Bramus\Router\Router();

        $routes = require(self::CONFIG_PATH.'/routes.php');
        
        foreach($routes as $path => $routeInfo){
            $method = $routeInfo['http_method'];
            $controller=$routeInfo['controller'];

            switch($method){
                case self::HTTP_GET:
                    $router->get($path,$controller);
                    break;
                case self::HTTP_POST:
                    $router->post($path,$controller);
                    break;
                case self::HTTP_PUT:
                    $router->put($path,$controller);
                    break;
                case self::HTTP_PATCH:
                    $router->patch($path,$controller);
                    break;
                case self::HTTP_OPTIONS:
                    $router->options($path,$controller);
                    break;
                case self::HTTP_DELETE:
                    $router->delete($path,$controller);
                    break;
                case self::HTTP_ANY;
                    $router->any($path,$controller);
                    break;
                default:
                    throw new \RuntimeException("Method ${method} is not defined");
            }
        }

        return $router;
    }

    public function run()
    {
        $this->di();
        $router = $this->routes();
        
        $router->run();
    }
}