<?php

namespace Tests\Services;

use App\Services\UserService;
use App\Services\VaccationService;

use Carbon\Carbon;

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

    public function testDeleteRejectedFails()
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

    public function testManagerAnotherUserDelete()
    {
        $user = $this->createTestUser(true,false);
        $manager = $this->createTestUser(true,true);

        $vaccations = $this->populateVaccationsToUser($user['user_id'],1);
        $vaccations=$vaccations[0];

        $dbService = $this->dBConnection();
        $user_service = new UserService($dbService,$this->dummyMail());
        $vaccationService = new VaccationService($dbService,$user_service);

        $this->assertFalse($vaccationService->delete($vaccations['vaccation_id'],$manager['user_id']));
    }

    public function testApprovedStatus()
    {
        $user = $this->createTestUser(true,false);


        $vaccations = $this->populateVaccationsToUser($user['user_id'],1);
        $vaccations=$vaccations[0];

        $dbService = $this->dBConnection();
        $user_service = new UserService($dbService,$this->dummyMail());
        
        $vaccationService = new VaccationService($dbService,$user_service);

        $this->assertTrue($vaccationService->changeVaccationStatus($vaccations['vaccation_id'],'APPROVED'));

        $sql = 'SELECT * FROM vaccations where vaccation_id = :vaccation_id';

        $stmt = $dbService->prepare($sql);
        $stmt->execute(['vaccation_id'=>$vaccations['vaccation_id']]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);


        $this->assertEquals($result['aproval_status'],'APPROVED');
    }


    public function testRejectedStatus()
    {
        $user = $this->createTestUser(true,false);


        $vaccations = $this->populateVaccationsToUser($user['user_id'],1);
        $vaccations=$vaccations[0];

        $dbService = $this->dBConnection();
        $user_service = new UserService($dbService,$this->dummyMail());
        
        $vaccationService = new VaccationService($dbService,$user_service);

        $this->assertTrue($vaccationService->changeVaccationStatus($vaccations['vaccation_id'],'REJECTED'));

        $sql = 'SELECT * FROM vaccations where vaccation_id = :vaccation_id';

        $stmt = $dbService->prepare($sql);
        $stmt->execute(['vaccation_id'=>$vaccations['vaccation_id']]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);


        $this->assertEquals($result['aproval_status'],'REJECTED');
    }


    public function testAddVaccation()
    {
        $user = $this->createTestUser(true,false);

        $sql = 'DELETE FROM vaccations';
        $dbService = $this->dBConnection();
        $stmt = $dbService->prepare($sql);
        $stmt->execute();


        $dbService = $this->dBConnection();
        $user_service = new UserService($dbService,$this->dummyMail());
        $vaccationService = new VaccationService($dbService,$user_service);

        $dt = Carbon::now();
        $status=$vaccationService->add($user['user_id'],$dt,$dt,"");

        $this->assertTrue($status);

        $sql = "SELECT * from vaccations where user_id=:user_id";
        $stmt = $dbService->prepare($sql);
        $stmt->execute([
            'user_id'=>$user['user_id'],
        ]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1,$results);
        
        $results=$results[0];

        $this->assertEquals($dt->format('Y-m-d'),$results['from']);
        $this->assertEquals($dt->format('Y-m-d'),$results['until']);
    }
}