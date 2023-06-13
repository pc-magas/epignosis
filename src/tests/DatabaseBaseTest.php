<?php

use App\Application;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class DatabaseTestCase extends TestCase {

    private $pdo;

    private $migrationManager;
    
    public function setUp ()
    {
        // For rubistness we place the configuration here
        // We avoid using phinx.php    
        $migration_config = [
            'paths' => [
                'migrations' => '%%PHINX_CONFIG_DIR%%/../db/migrations',
                'seeds' => '%%PHINX_CONFIG_DIR%%/../db/seeds'
            ],
            'environments' => [
                'default_migration_table' => 'phinxlog',
                'default_environment' => 'testing',
                'testing' => [
                    'adapter' => 'mysql',
                    'host' =>  $_ENV['DB_HOST'],
                    'name' => $_ENV['DB_NAME'],
                    'user' => $_ENV['DB_USER'],
                    'pass' => $_ENV['DB_PASSWD'],
                    'port' => $_ENV['DB_PORT']??'3306',
                    'charset' => 'utf8',
                ],
            ],
            'version_order' => 'creation'
        ];

        // Configs are same
        $pdo = Application::createDB($migration_config['environments']['testing']);
        
        $config = new Config($migration_config);
        $manager = new Manager($config, new StringInput(' '), new NullOutput());
        $manager->migrate('testing');
        // You can change default fetch mode after the seeding
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $this->pdo = $pdo;

        $this->migrationManager = $manager;
    }


    public function getMigrationManager()
    {
        return $this->migrationManager;
    }

    public function dnConnection()
    {
        return $this->pdo;
    }

}