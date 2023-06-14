<?php

namespace App\Services;

use App\Exceptions\LinkHasExpiredException;
use App\Exceptions\UserAlreadyExistsException;
use App\Exceptions\UserNotFoundException;

use App\Utils\Generic;

use Carbon\Carbon;
use Symfony\Component\Mailer\MailerInterface;
use PDO;

/**
 * User Management Logic
 */
class UserService
{
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

        $stmt= $this->dbConnection->prepare($sql);
        $stmt->execute(['timestamp'=>\Carbon\Carbon::now()->format('Y-m-d H:i:s'),'email'=>$email]);

        return $result;
    }

    /**
     * Reguster User
     *
     * @param string $email
     * @param string $password
     * @param string $fullname
     * @return void
     */
    public function registerUser(string $email, string $password, string $fullname)
    {

        $email = trim($email);
        $fullname = trim($fullname);
        $password = trim($password);


        if(!Generic::validateEmail($email)){
            throw new \InvalidArgumentException('Email is not a valid one');
        }

        if(empty($password)){
            throw new \InvalidArgumentException('Password is empty');
        }

        $password = password_hash($password);

        try {

            $sql = "INSERT INTO users 
                (
                    email,
                    password,
                    fullname,
                    activation_token,
                    expitation_token,
                    active
                ) 
            VALUES 
                (
                    :email,
                    :pass,
                    :fullname,
                    :token,
                    :activation_token,
                    :expiration_dt
                    false
                );";

            $stmt = $this->dbConnection->prepare($sql);

            // Strip tags for XSS prevention
            $stmt->prepare([
                'email' => strip_tags($email),
                'fullname' => strip_tags($fullname),
                'pass' => password_hash($password),
                'token' => substr(base64(random_bytes(100)),0,60),
                'expiration_dt' => Carbon::now()->modify('+24 hours')->format('Y-m-d H:i:s')
            ]);

        }catch(\PDOException $e){
            if((int)$e->getCode() == 2300){
                throw new UserAlreadyExistsException();
            }

            throw new \RuntimeException();
        }


        

    }
}