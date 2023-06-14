<?php

namespace Tests\Services;

use App\Exceptions\UserNotFoundException;
use App\Services\UserService;
use Tests\DatabaseTestCase;

use Carbon\Carbon;

class UserServiceTest extends DatabaseTestCase
{
    public function testUserLoginSuccess()
    {
        $user = $this->createTestUser();

        $service = new UserService($this->dBConnection(),$this->dummyMail());
        
        $info = $service->login($user['email'],'1234');
        
        $expectedUser_id=(int)$user['user_id'];
        $retrievedUser_id=(int)$info['user_id'];
        $this->assertEquals($expectedUser_id,$retrievedUser_id,"User Id not the Same");
    }

    public function testUserLoginWrongPassword()
    {
        $user = $this->createTestUser();
        $service = new UserService($this->dBConnection(),$this->dummyMail());

        $this->expectException(\RuntimeException::class);
        $service->login($user['email'],'lalalala');
    }


    public function testUserLoginWrongUser()
    {
        $user = $this->createTestUser();
        $service = new UserService($this->dBConnection(),$this->dummyMail());


        $email='l'.$user['email'];

        $this->expectException(UserNotFoundException::class);
        $service->login($email,'1234');
    }

    public function testRegisterManagerSuccess()
    {
        /**
         * @var 
         */
        $mailer = $this->dummyMail();
        $mailer->expects($this->once())->method('send');

        /**
         * @var \PDO
         */
        $conn = $this->dBConnection();


        $service = new UserService($conn,$mailer);

        Carbon::setTestNow(new Carbon('2023-06-12 00:00:00'));

        $success = $service->registerUser('test@example.com','1234','Test User','MANAGER');
        $this->assertTrue($success);

        $stmt = $conn->prepare("SELECT * from users where email=:email");
        $stmt->execute(['email'=>'test@example.com']);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals('test@example.com',$result['email']);
        $this->assertTrue(password_verify('1234',$result['password']));

        $this->assertEquals(0,$result['active']);
        $this->assertNotEmpty($result['activation_token']);
        $this->assertEquals('2023-06-13 00:00:00',$result['token_expiration']);

        $this->assertEquals('MANAGER',$result['role']);   
    }

    public function testUserRegisterEmployee()
    {
        /**
         * @var 
         */
        $mailer = $this->dummyMail();
        $mailer->expects($this->once())->method('send');

        /**
         * @var \PDO
         */
        $conn = $this->dBConnection();


        $service = new UserService($conn,$mailer);

        Carbon::setTestNow(new Carbon('2023-06-12 00:00:00'));

        $success = $service->registerUser('test@example.com','1234','Test User','EMPLOYEE');
        $this->assertTrue($success);

        $stmt = $conn->prepare("SELECT * from users where email=:email");
        $stmt->execute(['email'=>'test@example.com']);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals('test@example.com',$result['email']);
        $this->assertTrue(password_verify('1234',$result['password']));

        $this->assertEquals(0,$result['active']);
        $this->assertNotEmpty($result['activation_token']);
        $this->assertEquals('2023-06-13 00:00:00',$result['token_expiration']);

        $this->assertEquals('EMPLOYEE',$result['role']);
    }

    public function testUserRegisterFailIfUserExists()
    {
        $user = $this->createTestUser();
        /**
         * @var \PDO
         */
        $conn = $this->dBConnection();
        $mailer = $this->dummyMail();

        $service = new UserService($conn,$mailer);

        $this->expectException(\App\Exceptions\UserAlreadyExistsException::class);
        $service->registerUser($user['email'],'1234',$user['fullname'],'EMPLOYEE');

    }

}