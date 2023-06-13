<?php

namespace Tests\Services;

use App\Services\UserService;
use Tests\DatabaseTestCase;

class UserServiceTest extends DatabaseTestCase
{
    public function testUserLoginSuccess()
    {
        $user = $this->createTestUser();

        $service = new UserService($this->dBConnection());
        
        $info = $service->login($user['email'],'1234');
        
        $expectedUser_id=(int)$user['user_id'];
        $retrievedUser_id=(int)$info['user_id'];
        $this->assertEquals($expectedUser_id,$retrievedUser_id,"User Id not the Same");
    }

    public function testUserLoginWrongPassword()
    {
        $user = $this->createTestUser();
        $service = new UserService($this->dBConnection());

        $this->expectException(\RuntimeException::class);
        $service->login($user['email'],'lalalala');
    }
}