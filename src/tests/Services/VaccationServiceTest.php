<?php

namespace Tests\Services;

use App\Services\UserService;
use App\Services\VaccationService;

use Tests\DatabaseTestCase;


class VaccationServiceTest extends DatabaseTestCase
{
    public function testDelete()
    {
        $user = $this->createTestUser(true,false);

        $vaccations = $this->populateVaccationsToUser($user['user_id'],1);
        $vaccations=$vaccations[0];

        $dbService = $this->dBConnection();
        $user_service = new UserService($dbService,$this->dummyMail());
        $vaccationService = new VaccationService($dbService,$user_service);

        $this->assertTrue($vaccationService->delete($vaccations['vaccation_id'],$user['user_id']));
    }

    public function testDeleteApprovedFails()
    {
        $user = $this->createTestUser(true,false);

        $vaccations = $this->populateVaccationsToUser($user['user_id'],1);
        $vaccations=$vaccations[0];

        $dbService = $this->dBConnection();

        $sql = "UPDATE vaccations SET aproval_status='APPROVED' where vaccation_id = :vaccation_id";
        
        $stmt = $dbService->prepare($sql);
        $stmt->execute(['vaccation_id'=>$vaccations['vaccation_id']]);
        
        $user_service = new UserService($dbService,$this->dummyMail());
        $vaccationService = new VaccationService($dbService,$user_service);

        $this->assertFalse($vaccationService->delete($vaccations['vaccation_id'],$user['user_id']));
    }


}