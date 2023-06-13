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
        $configArray = require(__DIR__.'../config/phinx.php');
        
        $config = $config['environments']['testing'];


        $pdo = Application::createDB($config);
        
        $config = new Config($configArray);
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