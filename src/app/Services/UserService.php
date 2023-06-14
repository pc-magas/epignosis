<?php

namespace App\Services;

use App\Application;
use App\Exceptions\LinkHasExpiredException;
use App\Exceptions\UserAlreadyExistsException;
use App\Exceptions\UserNotFoundException;

use App\Utils\Generic;

use Carbon\Carbon;
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
            throw new \InvalidArgumentException("ROLE $userRole is an invalid one");
        }

        $email = trim($email);
        $fullname = trim($fullname);
        $password = trim($password);

        if(!Generic::validateEmail($email)){
            throw new \InvalidArgumentException('Email is not a valid one');
        }

        if(empty($password)){
            throw new \InvalidArgumentException('Password is empty');
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
                    false
                );";

            $stmt = $this->dbConnection->prepare($sql);

            // Strip tags for XSS prevention
            $stmt->execute([
                'email' => strip_tags($email),
                'fullname' => strip_tags($fullname),
                'pass' => $password,
                'token' => substr(base64_encode(random_bytes(100)),0,60),
                'expiration_dt' => Carbon::now()->modify('+24 hours')->format('Y-m-d H:i:s'),
                'role'=>$userRole
            ]);

        }catch(\PDOException $e){
            if((int)$e->getCode() == 2300){
                throw new UserAlreadyExistsException();
            }

            return false;
        }


        $email = (new Email())
            ->from('hello@example.com')
            ->to($email)
            ->subject('Account Activation')
            ->text("An account has been activated please visit at ".Generic::getAppUrl("/activate")." in order for your account to be activated");

        $this->mailer->send($email);

        return true;
    }
}