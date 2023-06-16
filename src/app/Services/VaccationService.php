<?php

namespace App\Services;

use App\Services\UserService;
use App\Utils\Generic;

use Carbon\Carbon;

class VaccationService
{
    /**
     * Database Handler
     *
     * @var PDO
     */
    private $dbConnection;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(\PDO $dbConnection,UserService $userService)
    {
        $this->dbConnection = $dbConnection;
        $this->userService = $userService;
    }

    /**
     * Insert a pending request
     *
     * @param integer $user_id A user Id. Intentionally there's no check if user_id is employee or manager for futureproofing reasons
     * @param Carbon $from Datetime from where the vaccation starts. It must be in format 
     * @param Carbon $until Datetime from where the vaccation ends
     * @param string $comment Miscellanous comment about the request
     * 
     * @return bool
     */
    public function add(int $user_id, Carbon $from, Carbon $until, string $comment):bool
    {
        if(!$this->userService->userExists($user_id)){
            throw new \InvalidArgumentException("User ${user_id} does not exist",UserService::INVALID_USER_ID);
        }

        if($until->lessThan($from)){
            throw new \ InvalidArgumentException("Invalid Range from SDatetime muse be LESS or equal to than until");
        }

        $sql = "INSERT INTO vaccations(user_id,`from`,until) VALUES (:user_id,:from,:until);";

        try {

            $stmt = $this->dbConnection->prepare($sql);
            $stmt->execute([
                'user_id'=>$user_id,
                'from'=>$from->format("Y-m-d"),
                'until'=>$until->format("Y-m-d")
            ]);
            
            return true;

        }catch(\PDOException $e){
            return false;
        }
    }

    /**
     * Retrieve a specific vaccation
     *
     * @param integer $vaccation_id
     * @return array With vaccation Info
     */
    public function findVaccation(int $vaccation_id):array
    {
        if($vaccation_id < 0){
            return [];
        }

        $sql = "SELECT * from vaccation where vaccation_id = :vaccation_id";
        
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute([
            'vaccation_id'=>$vaccation_id
        ]);
        

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * List Users
     *
     * @param integer $user_id 
     * @param integer $page
     * @param integer $limit
     * @return array
     */
    public function list(int $user_id, int $page, int $limit):array
    {

        $limit = $limit<=0?10:$limit;

        $sql = "SELECT count(*) from vaccations where user_id = :user_id";
        
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute(['user_id'=>$user_id]);   
        
        $count = $stmt->fetch(\PDO::FETCH_COLUMN); 

        $pages = Generic::calculateNumberOfPages($limit,$count);

        if($page > $pages){
            return [];
        }

        $offset = Generic::calculateOffset($page,$limit);

        $sql = "
            SELECT 
                from,until,status,created
            from 
                created_timestamp
            where
                user_id=:user_id
            order by created_timestamp ASC
            LIMIT :offset , :limit
        ";

        $stmt = $this->dbConnection->prepare($sql);
        
        $stmt->bindParam('offset',$offset,\PDO::PARAM_INT);
        $stmt->bindParam('limit',$limit,\PDO::PARAM_INT);
        $stmt->bindParam('user_id',$user_id,\PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Delete a Vaccation
     *
     * @param integer $vaccation_id 
     * @param integer $deleting_user_id
     * @return boolean
     */
    public function delete(int $vaccation_id, int $deleting_user_id):bool
    {
        $vaccation = $this->findVaccation($vaccation_id);

        if(empty($vaccation['$vaccation_id'])){
            return false;
        }

        if($deleting_user_id <= 0) {
            throw new \InvalidArgumentException("User Id is invalid $deleting_user_id",UserService::INVALID_USER_ID);
        }

        if((int)$vaccation['user_id'] != $deleting_user_id){
            return false;
        }

        if($vaccation['aproval_status'] != 'PENDING'){
            return false;
        }

        $sql = "DELETE from vaccations where vaccation_id = :vaccation_id and status='PENDING'";

        $this->dbConnection->beginTransaction();
        try {
            
            $stmt = $this->dbConnection->prepare($sql);
            $stmt->prepare(['vaccation_id'=>$vaccation_id]);
            $stmt->execute();

            $this->dbConnection->commit();
            
            return true;
        }catch(\PDOException $e){
            $this->dbConnection->rollback();
            return false;
        }
    }

    /**
     * Approve or reject a vaccation
     *
     * @param integer $vaccation_id The vaccation Identifier
     * @param string $status Reservation Status in must be either 'APPROVED' or 'REJECTED'
     * @return boolean
     */
    public function changeVaccationStatus(int $vaccation_id, string $status):bool
    {

        $status = trim($status);
        $status = strtoupper($status);

        if(!in_array($status,['APPROVED','REJECTED'])){
            return false;
        }

        $vaccation = $this->findVaccation($vaccation_id);

        if($vaccation['status'] != 'PENDING'){
            return false;
        }

        $sql = "UPDATE vaccations set aproval_status=:status where vaccation_id = :vaccation_id and status='PENDING'";

        $this->dbConnection->beginTransaction();
        try {
            
            $stmt = $this->dbConnection->prepare($sql);
            $stmt->execute(['vaccation_id'=>$vaccation_id,'status'=>$status]);

            $this->dbConnection->commit();
            
            return true;
        }catch(\PDOException $e){
            $this->dbConnection->rollback();
            return false;
        }

    }
}