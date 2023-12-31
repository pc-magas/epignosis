<?php

namespace App\Services;

use App\Exceptions\UserAlreadyExistsException;
use App\Exceptions\UserNotFoundException;

use App\Utils\Generic;

use Carbon\Carbon;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Mailer\MailerInterface;

use Symfony\Component\Mime\Email;

use PDO;
use Symfony\Component\Mime\Exception\InvalidArgumentException;

/**
 * User Management Logic
 */
class UserService
{
    /**
     * Error Code for empty email
     */
    const EMPTY_EMAIL_ERROR = 1;

    /**
     * Error code for empty password
     */
    const EMPTY_PASSWORD_ERROR = 3;
    
    /**
     * Error code for invalid email
     */

    const INVALID_EMAIL_ERROR = 2;

    /**
     * Error code for invalid role
     */
    const INVALID_ROLE_ERROR = 4;

    /**
     * Error code for all parameters are missing or invalid
     */
    const INVALID_PARAMS_ALL = 0;

    /**
     * Error For invalid User Id
     */
    const INVALID_USER_ID = 5;

    /**
     * Database Handler
     *
     * @var PDO
     */
    private $dbConnection;

    /**
     * @var MailerInterface
     */
    private $mailer ;

    public function __construct(PDO $dbConnection, MailerInterface $mailer)
    {
        $this->dbConnection = $dbConnection;
        $this->mailer = $mailer;
    }

    /**
     * Login User
     *
     * @param string $email User Email
     * @param string $password User Password
     * @return array With User Info
     * 
     * @throws \InvalidArgumentException If arguments are nor provided correctly
     * @throws UserNotFoundException If User does not exist
     * @throws \RuntimeException If Password authentication fails
     */
    public function login(string $email, string $password): array
    {
        $email = trim($email);

        $password = trim($password);

        if(!Generic::validateEmail($email)){
            throw new \InvalidArgumentException('Email is not a valid one');
        }

        if(empty($password)){
            throw new \InvalidArgumentException('Password is empty');
        }

        $sql = "SELECT * from users where email = :email and active=true LIMIT 1";

        $stmt= $this->dbConnection->prepare($sql);
        $stmt->execute(['email'=>$email]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(empty($result)){
            throw new UserNotFoundException($email);
        }

        if(!password_verify($password,$result['password'])){
            throw new \RuntimeException('Password fails to verify');
        }

        // Password must NOT be accessible anywhere beyond login and resgistration
        unset($result['password']);
        $result['active'] = $result['active']==1?true:false;

        $sql = "UPDATE users SET last_login = :timestamp where email = :email";

        $this->dbConnection->beginTransaction();
        try{
            $stmt= $this->dbConnection->prepare($sql);
            $stmt->execute(['timestamp'=>\Carbon\Carbon::now()->format('Y-m-d H:i:s'),'email'=>$email]);
            $this->dbConnection->commit();
        } catch(\Exception $e){
            $this->dbConnection->rollback();
            // User is logged in there's no point to avoid user login because
            // last login date could not be updated
        }

        return $result;
    }

    /**
     * Register a User
     *
     * @param string $email
     * @param string $password
     * @param string $fullname
     * @return bool
     */
    public function registerUser(string $email, string $password, string $fullname, string $userRole):bool
    {

        $userRole = trim($userRole);

        if(!in_array($userRole,['MANAGER','EMPLOYEE'])){
            throw new \InvalidArgumentException("ROLE $userRole is an invalid one",self::INVALID_ROLE_ERROR);
        }

        $email = trim($email);
        $fullname = trim($fullname);
        $password = trim($password);

        if(!Generic::validateEmail($email)){
            throw new \InvalidArgumentException('Email is not a valid one',self::INVALID_EMAIL_ERROR);
        }

        if(empty($password)){
            throw new \InvalidArgumentException('Password is empty',self::EMPTY_PASSWORD_ERROR);
        }

        $password = password_hash($password,PASSWORD_DEFAULT);

        try {

            $sql = "INSERT INTO users 
                (
                    email,
                    password,
                    fullname,
                    activation_token,
                    token_expiration,
                    role,
                    active
                ) 
            VALUES 
                (
                    :email,
                    :pass,
                    :fullname,
                    :token,
                    :expiration_dt,
                    :role,
                    true
                );";

            $stmt = $this->dbConnection->prepare($sql);

            // Strip tags for XSS prevention
            $stmt->execute([
                'email' => strip_tags($email),
                'fullname' => strip_tags($fullname),
                'pass' => $password,
                'token' => Generic::createUrlSafeToken(60),
                'expiration_dt' => Carbon::now()->modify('+24 hours')->format('Y-m-d H:i:s'),
                'role'=>$userRole
            ]);

        }catch(\PDOException $e){

            // Email has Unique index therefore I use the SQL code for duplicate record
            // https://mariadb.com/kb/en/mariadb-error-codes/
            // $e->getCode() stores the codes mentioned above
            if((int)$e->getCode() == 23000){
                throw new UserAlreadyExistsException();
            }

            return false;
        }

        return true;
    }

    public function getByToken(string $token)
    {
        $sql = "SELECT user_id,token_expiration from users where activation_token=:token";

        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute(['token'=>$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if User exists
     *
     * @param integer $user_id
     * @return boolean
     */
    public function userExists(int $user_id): bool
    {
        if($user_id < 0 ){
            return false;
        }

        $sql = "SELECT EXISTS(SELECT 1 from users where user_id = :user_id LIMIT 1)";
        
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_COLUMN);
        return $result == 1;

    }

    /**
     * Delete User
     *
     * @param integer $user_id User Id
     * @return boolean
     */
    public function deleteUser(int $user_id): bool
    {
        if(!$this->userExists($user_id)){
            return false;
        }

        $this->dbConnection->beginTransaction();
        try {

            // Vaccations will be cascaded. A cascade trigger must be placed
            $sql = "DELETE FROM users where user_id=:user_id";
            $stmt = $this->dbConnection->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);

            $this->dbConnection->commit();

        } catch(\PDOException $e) {
            $this->dbConnection->rollBack();

            return false;
        }

        return true;
    }

    /**
     * Edits email and user's name
     * Password update needs to be handled differently.
     * 
     * Empty values are ignored.
     * 
     * @param integer $user_id User's Id
     * @param string $email User's name
     * @param string $fullname 
     * @return bool
     * 
     * @throws \InvalidArgumentException
     */
    public function modifyEmailAndName(int $user_id,?string $email=null, ?string $fullname=null): bool
    {
        if(!$this->userExists($user_id)){
            return false;
        }

        $sql = "UPDATE users set %s where user_id = :user_id";
        $updateColSql = [];

        $columnsToUpdate = ['user_id'=>$user_id];

        $email = trim($email);

        if(!empty($email)){
            if(!Generic::validateEmail($email)){
                throw new \InvalidArgumentException('Email is not a valid one',self::INVALID_EMAIL_ERROR);
            }
            
            $updateColSql[] = "email=:email";
            $columnsToUpdate['email']=$email;
        }
        
        $fullname = trim($fullname);

        if(!empty($fullname)){
            $updateColSql[] = "fullname=:fullname";
            $columnsToUpdate['fullname']=$fullname;
        }

        $updateSql = trim(implode(',',$updateColSql),',');

        $sql = sprintf($sql,$updateSql);

        if(count($updateColSql) == 0){
            throw new \InvalidArgumentException('No Arguments Provided',self::INVALID_PARAMS_ALL);
        }

        $this->dbConnection->beginTransaction();

        try {
            
            $stmt = $this->dbConnection->prepare($sql);
            $stmt->execute($columnsToUpdate);

            $this->dbConnection->commit();
        }catch(\PDOException $e) {
            $this->dbConnection->rollBack();
            return false;
        }

        return true;
    }

    /**
     * Update User's password
     * 
     * @param integer $user_id User's user_id
     * @param string $password User's password
     * 
     * @return bool
     * 
     * @throws InvalidArgumentException
     * 
     */
    public function updatePassword(int $user_id, string $password):bool
    {
        if(!$this->userExists($user_id)){
            return false;
        }

        if(empty($password)){
            throw new \InvalidArgumentException('Password is empty',self::EMPTY_PASSWORD_ERROR);
        }

        $password = password_hash($password,PASSWORD_DEFAULT);

        $sql = "UPDATE users set password=:password where user_id = :user_id";

        $this->dbConnection->beginTransaction();

        try {
            
            $stmt = $this->dbConnection->prepare($sql);
            $stmt->execute(['user_id'=>$user_id,'password'=>$password]);

            $this->dbConnection->commit();
        }catch(\PDOException $e) {
            $this->dbConnection->rollBack();

            return false;
        }

        return true;
    }

    /**
     * Get User List
     *
     * @param integer $page
     * @param integer $limit
     * @return array
     * 
     * @throws \InvalidArgumentException
     * @throws \PDOException
     */
    public function listUsers(int $page, int $limit,?int &$pages):array
    {
        $limit = $limit<=0?10:$limit;
        $count = $this->dbConnection->query("SELECT count(*) from users",PDO::FETCH_COLUMN,0)->fetch();
        $pages = Generic::calculateNumberOfPages($limit,$count);

        if($page > $pages){
            return [];
        }

        $offset = Generic::calculateOffset($page,$limit);

        $sql = "
            SELECT 
                user_id,email,fullname,active,last_login
            from 
                users
            order by fullname ASC
            LIMIT :offset , :limit
        ";

        $stmt = $this->dbConnection->prepare($sql);
        
        $stmt->bindParam('offset',$offset,PDO::PARAM_INT);
        $stmt->bindParam('limit',$limit,PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    /**
     * Retrieve a single user Information
     *
     * @param integer $user_id The user's Id
     * @return array With user Info
     * 
     * @throws \InvalidArgumentException In case that user_id <=0
     */
    public function getUserInfo(int $user_id):array
    {
        if($user_id <= 0 ){
            return new \InvalidArgumentException("User Id is invalid ${user_id}");
        }

        $sql = "SELECT * from users where user_id = :user_id LIMIT 1";
        
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

   /**
    * Send Activation Email 
    * and create activation token
    *
    * @param string $email
    * @return boolean
    */
    public function sendResetPasswordEmail(string $email):bool
    {
        if(empty($email) || !Generic::validateEmail($email)){
            throw new \InvalidArgumentException('Email is not a valid one',self::INVALID_EMAIL_ERROR);
        }

        $sql = "SELECT * from users where email=:email and active=true";

        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute(['email'=>$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if(empty($user)){
            return false;
        }

        $sql = "UPDATE users SET activation_token=:token, token_expiration=:expiration_date where user_id = :user_id";
     
        $this->dbConnection->beginTransaction();

        $token = Generic::createUrlSafeToken(60);
        try {
            
            $stmt = $this->dbConnection->prepare($sql);
            $stmt->execute([
                'user_id' => $user['user_id'],
                'token'   => $token,
                'expiration_date' => Carbon::now()->modify('+1 hour') // we need no more than this.
            ]);

            $this->dbConnection->commit();
        }catch(\PDOException $e) {
            $this->dbConnection->rollBack();
            return false;
        }


        $email = (new Email())
            ->from('hello@example.com')
            ->to($email)
            ->subject('Account Activation')
            ->text("An account has been activated please visit at ".Generic::getAppUrl("/pr/${token}")." in order for your account to be activated");

        $this->mailer->send($email);

        return true;
    }


    /**
     * Reset User Password
     *
     * @param string $token Activation token
     * @param string $password New Password
     * @return bool
     */
    public function resetUserPassword(string $token, string $password):bool
    {
        if(empty($token)){
            return false;
        }

        if(empty($password)){
            return false;
        }

        $user = $this->getByToken($token);

        if(empty($user)){
            return false;
        }

        $password= password_hash($password,PASSWORD_DEFAULT);

        $this->dbConnection->beginTransaction();
        try{

            $stmt = $this->dbConnection->prepare("UPDATE users SET activation_token=NULL,token_expiration=NULL,password=:password where user_id=:user_id");
            $stmt->execute(['user_id'=>$user['user_id'],'password'=>$password]);
            $stmt->execute();
            $this->dbConnection->commit();
        } catch(\PDOException $e){
            $this->dbConnection->rollback();
            return false;
        }

        return true;
    }
}