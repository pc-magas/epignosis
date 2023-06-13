<?php

namespace Tests;

use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

use PHPUnit\Framework\TestCase;

use App\Application;

class DatabaseTestCase extends TestCase {

    private $pdo;

    private $migrationManager;
    
    public function setUp (): void
    {
        // For rubistness we place the configuration here
        // We avoid using phinx.php    
        $migration_config = [
            'paths' => [
                'migrations' => __DIR__.'/../db/migrations',
                'seeds' => __DIR__.'/../db/seeds'
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
        $this->pdo = $pdo;

        $this->migrationManager = $manager;
    }

    public function tearDown():void
    {
        $this->migrationManager->rollback('testing');
    }


    public function getMigrationManager()
    {
        return $this->migrationManager;
    }

    public function dBConnection()
    {
        return $this->pdo;
    }

    /**
     * Create a Test User
     *
     * Default password will be 1234
     * 
     * @param boolean $active Whether User is active or Not
     * @param boolean $manager Whether User is manager or Not
     * @return array with User Info
     */
    public function createTestUser(bool $active=true,bool $manager=false)
    {
        $db = $this->dBConnection();

        $sql = "INSERT INTO users (email,fullname,password,role,active,activation_token) VALUES (:email,:fullname,:pass,:role,:active,:token);";

        $prefix=time();
        $data = [
            'email'=>$prefix.'@example.com',
            'fullname'=>'TEST '.$prefix,
            'pass'=>password_hash('1234',PASSWORD_DEFAULT),
            'role'=>$manager?'MANAGER':'EMPLOYEE',
            'active'=>$active,
            'token'=> $active?NULL:substr(base64_encode(random_bytes(12)),0,60)
        ];

        $stmt=$db->prepare($sql);
        $stmt->execute($data);
        $id = $db->lastInsertId();

        $data['user_id'] = $id;

        return $data;
    }
}