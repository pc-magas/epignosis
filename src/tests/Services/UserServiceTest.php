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
        
        $this->assertEquals((int)$user['user_id'],(int)$info['user_id']);
    }
}