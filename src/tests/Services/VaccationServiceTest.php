<?php

namespace Tests\Services;

use App\Services\UserService;
use App\Services\VaccationService;

use Carbon\Carbon;

use Carbon\CarbonImmutable;
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

        $dbService = $this->dBConnection();
        $user_service = new UserService($dbService,$this->dummyMail());
        $vaccationService = new VaccationService($dbService,$user_service);

        $dt = Carbon::now();
        $status=$vaccationService->addPendingVaccationRequest($user['user_id'],$dt,$dt,"");

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

    public function testAddVaccationDuplicatePendingFails()
    {
        $user = $this->createTestUser(true,false);

        $dbService = $this->dBConnection();

        // Ensure no garbage left
        // Just Foolproofing the test
        $sql = 'DELETE FROM vaccations';
        $stmt = $dbService->prepare($sql);
        $stmt->execute();

        $insertFrom = new Carbon();
        $insertUntil_orig = (new CarbonImmutable())->modify('+3 days');

        // I manually insert a vaccation in order to have controll to the insertion time
        $sql = "INSERT INTO vaccations(user_id,`from`,until,aproval_status) VALUES (:user_id,:from,:until,'PENDING');";
        $stmt = $dbService->prepare($sql);
        $stmt->execute([
            'user_id'=>$user['user_id'],
            'from'=>$insertFrom->format('Y-m-d'),
            'until'=>$insertUntil_orig->format('Y-m-d')
        ]);

        $insertUntil = (new Carbon($insertFrom))->modify("+10 days");

        $dbService = $this->dBConnection();
        $user_service = new UserService($dbService,$this->dummyMail());
        $vaccationService = new VaccationService($dbService,$user_service);

        $status = $vaccationService->addPendingVaccationRequest($user['user_id'],new Carbon($insertFrom),$insertUntil,"");

        $this->assertFalse($status);

        $sql = "SELECT * from vaccations where user_id=:user_id";
        $stmt = $dbService->prepare($sql);
        $stmt->execute([
            'user_id'=>$user['user_id'],
        ]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1,$results);
    }

   
    public function testAddVaccationDuplicateApprovedFails()
    {
        $user = $this->createTestUser(true,false);

        $dbService = $this->dBConnection();

        // Ensure no garbage left
        // Just Foolproofing the test
        $sql = 'DELETE FROM vaccations';
        $stmt = $dbService->prepare($sql);
        $stmt->execute();

        $insertFrom = new Carbon();
        $insertUntil_orig = (new Carbon())->modify('+3 days');

        // I manually insert a vaccation in order to have controll to the insertion time
        $sql = "INSERT INTO vaccations(user_id,`from`,until,aproval_status) VALUES (:user_id,:from,:until,'APPROVED');";
        $stmt = $dbService->prepare($sql);
        $stmt->execute([
            'user_id'=>$user['user_id'],
            'from'=>$insertFrom->format('Y-m-d'),
            'until'=>$insertUntil_orig->format('Y-m-d')
        ]);

        $insertUntil = (new Carbon($insertFrom))->modify("+10 days");

        $dbService = $this->dBConnection();
        $user_service = new UserService($dbService,$this->dummyMail());
        $vaccationService = new VaccationService($dbService,$user_service);

        $status = $vaccationService->addPendingVaccationRequest($user['user_id'],$insertFrom,$insertUntil,"");

        $this->assertFalse($status);

        $sql = "SELECT * from vaccations where user_id=:user_id";
        $stmt = $dbService->prepare($sql);
        $stmt->execute([
            'user_id'=>$user['user_id'],
        ]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1,$results);
    }

    public function testAddVaccationDuplicateRejectedFails()
    {
        $user = $this->createTestUser(true,false);

        $dbService = $this->dBConnection();

        // Ensure no garbage left
        // Just Foolproofing the test
        $sql = 'DELETE FROM vaccations';
        $stmt = $dbService->prepare($sql);
        $stmt->execute();

        $insertFrom = new Carbon();
        $insertUntil_orig = (new Carbon())->modify('+3 days');

        // I manually insert a vaccation in order to have controll to the insertion time
        $sql = "INSERT INTO vaccations(user_id,`from`,until,aproval_status) VALUES (:user_id,:from,:until,'APPROVED');";
        $stmt = $dbService->prepare($sql);
        $stmt->execute([
            'user_id'=>$user['user_id'],
            'from'=>$insertFrom->format('Y-m-d'),
            'until'=>$insertUntil_orig->format('Y-m-d')
        ]);

        $insertUntil = (new Carbon($insertFrom))->modify("+10 days");

        $dbService = $this->dBConnection();
        $user_service = new UserService($dbService,$this->dummyMail());
        $vaccationService = new VaccationService($dbService,$user_service);

        $status = $vaccationService->addPendingVaccationRequest($user['user_id'],$insertFrom,$insertUntil,"");

        $this->assertFalse($status);

        $sql = "SELECT * from vaccations where user_id=:user_id";
        $stmt = $dbService->prepare($sql);
        $stmt->execute([
            'user_id'=>$user['user_id'],
        ]);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1,$results);
    }

    public function testListVaccationsUser()
    {
        $vaccationsAsAssodByVaccationId = function($final_array,$item){
            $final_array[(int)$item['vaccation_id']]=$item;
            return $final_array;
        };

        $user = $this->createTestUser(true,false);
        $user2 = $this->createTestUser(true,false);

        $returendVaccations = $this->populateVaccationsToUser($user['user_id'],20);
        $returendVaccations = array_reduce($returendVaccations,$vaccationsAsAssodByVaccationId,[]);
        
        $vaccations2 = $this->populateVaccationsToUser($user2['user_id'],20);
        $ignoredVaccations = array_reduce($vaccations2,$vaccationsAsAssodByVaccationId,[]);

        $dbService = $this->dBConnection();
        $user_service = new UserService($dbService,$this->dummyMail());
        $vaccationService = new VaccationService($dbService,$user_service);

        $listedVaccations = $vaccationService->list(1,10,$pages,$user['user_id']);
        $this->assertEquals(20/10,$pages);

        $this->assertCount(10,$listedVaccations);
        $this->assertNotEquals(count($listedVaccations),count($returendVaccations));

        foreach($listedVaccations as $vaccation){

            $this->assertFalse(in_array($vaccation['vaccation_id'],array_keys($ignoredVaccations)));

            $this->assertTrue(isset($returendVaccations[(int)$vaccation['vaccation_id']]));

            $vaccationTocheck = $returendVaccations[$vaccation['vaccation_id']];

            $this->assertEquals($user['fullname'],$vaccation['user_name']);
            $this->assertNotEquals($user2['fullname'],$vaccation['user_name']);


            $this->assertEquals($vaccationTocheck['from'],$vaccation['from']);
            $this->assertEquals($vaccationTocheck['until'],$vaccation['until']);
            $this->assertEquals($vaccationTocheck['aproval_status'],$vaccation['aproval_status']);
        }
    }

    public function testListVaccationsWithoutUser()
    {
        $vaccationsAsAssodByVaccationId = function($final_array,$item){
            $final_array[(int)$item['vaccation_id']]=$item;
            return $final_array;
        };

        $user = $this->createTestUser(true,false);
        $user2 = $this->createTestUser(true,false);

        $returendVaccations = $this->populateVaccationsToUser($user['user_id'],10);       
        $vaccations2 = $this->populateVaccationsToUser($user2['user_id'],20);
        $returendVaccations3 = $this->populateVaccationsToUser($user['user_id'],10);

        $returendVaccations = array_merge($returendVaccations,$vaccations2,$returendVaccations3);
        $returendVaccations = array_reduce($returendVaccations,$vaccationsAsAssodByVaccationId,[]);

        $dbService = $this->dBConnection();
        $user_service = new UserService($dbService,$this->dummyMail());
        $vaccationService = new VaccationService($dbService,$user_service);

        $listedVaccations = $vaccationService->list(1,10,$pages);

        $this->assertCount(10,$listedVaccations);
        $this->assertNotEquals(count($listedVaccations),count($returendVaccations));

        $this->assertEquals(30/10,$pages);

        foreach($listedVaccations as $vaccation){

            $this->assertTrue(isset($returendVaccations[(int)$vaccation['vaccation_id']]));

            $vaccationTocheck = $returendVaccations[$vaccation['vaccation_id']];

            $this->assertTrue(in_array($vaccation['user_name'],[$user['fullname'],$user2['fullname']]));

            $this->assertEquals($vaccationTocheck['from'],$vaccation['from']);
            $this->assertEquals($vaccationTocheck['until'],$vaccation['until']);
            $this->assertEquals($vaccationTocheck['aproval_status'],$vaccation['aproval_status']);
        }
    }
}