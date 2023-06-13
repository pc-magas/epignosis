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

        try {
            $info = $service->login($user['email'],'1234');
        } catch(\Exception $e){
            $this->fail($e->getMessage());
        }

        $this->assertEquals($user['user_id'],$info['user_id']);
    }
}