<?php

namespace Tests;

use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;


use App\Application;

class DatabaseTestCase extends \Tests\TestBase {

    private $pdo;

    private $migrationManager;
    

    public function setUp(): void
    {
        parent::setUp();

        error_reporting(E_ALL);

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
        
        if(!empty($this->pdo)){
            $this->pdo = $pdo;
        }

        $config = new Config($migration_config);
        $manager = new Manager($config, new StringInput(' '), new NullOutput());
        $this->migrationManager = $manager;

        try{
            $this->migrationManager->rollback('testing','all');
        }catch(\Exception $e){
            
        }
        $this->migrationManager->migrate('testing');

        // You can change default fetch mode after the seeding
        $this->pdo = $pdo;
    }

    public function tearDown():void
    {
        parent::tearDown();
        $this->migrationManager->rollback('testing','all');
    }


    public function getMigrationManager()
    {
        return $this->migrationManager;
    }

    public function dBConnection(): \PDO
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
     * 
     * @return array with User Info
     * 
     */
    public function createTestUser(bool $active=true,bool $manager=false)
    {
        $db = $this->dBConnection();

        $sql = "INSERT INTO users (email,fullname,password,role,active,activation_token) VALUES (:email,:fullname,:pass,:role,:active,:token);";

        $prefix=microtime();
        $prefix=str_replace(['.',' '],'',(string)$prefix);
        $data = [
            'email'=>$prefix.'@example.com',
            'fullname'=>'TEST '.$prefix,
            'pass'=>password_hash('1234',PASSWORD_DEFAULT),
            'role'=>$manager?'MANAGER':'EMPLOYEE',
            'active'=>$active?1:0,
            'token'=> $active?NULL:substr(base64_encode(random_bytes(12)),0,60)
        ];

        $stmt=$db->prepare($sql);
        $stmt->execute($data);
        $id = $db->lastInsertId();

        $data['user_id'] = $id;

        return $data;
    }

    /**
     * Populate test Vacccations
     *
     * @param integer $user_id
     * @param integer $numberOfRecords If <= 0, 1 record will be returned 
     * @return array
     */
    public function populateVaccationsToUser(int $user_id, int $numberOfRecords = 1):array
    {
        $dbService = $this->dBConnection();

        // Ensure no garbage left
        // Just Foolproofing the test
        $sql = 'DELETE FROM vaccations where user_id=:user_id';
        $stmt = $dbService->prepare($sql);
        $stmt->execute(['user_id'=>$user_id]);

        $vaccations=[];

        $now = \Carbon\Carbon::now();

        $sql = "INSERT INTO vaccations(user_id,`from`,until) VALUES (?,?,?) RETURNING * ;";

        $numberOfRecords = ($numberOfRecords<=0)?1:$numberOfRecords;

        $db = $this->dBConnection();
        $stmt=$db->prepare($sql);
        while($numberOfRecords>0){
            $stmt->execute([$user_id,$now->format('Y-m-d'),$now->format('Y-m-d')]);
            $vaccations[]=$stmt->fetch(\PDO::FETCH_ASSOC);
            $now->modify("+30 days");
            $numberOfRecords--;
        }

        return $vaccations;
    }

   
}