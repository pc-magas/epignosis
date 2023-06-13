<?php

namespace App\Services;

use PDO;

use App\Exceptions\UserNotFoundException;

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

    public function __construct(PDO $dbConnection)
    {
        $this->dbConnection = $dbConnection;
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

        if(empty($email)){
            throw new \InvalidArgumentException('Email is empty');
        }

        if(empty($password)){
            throw new \InvalidArgumentException('Password is empty');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
            throw new \InvalidArgumentException('Email is not a valid one');
        }

        $sql = "SELECT * from users where email = :email and active=true LIMIT 1";

        $stmt= $this->dbConnection->prepare($sql);
        $stmt->execute(['email'=>$email]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
       
        if(!isset($result)){
            throw new UserNotFoundException($email);
        }

        if(!password_verify($password,$result['password'])){
            throw new \RuntimeException('Password fails to verify');
        }

        // POassword must NOT be accessible anywhere beyond login and resgistration
        unset($result['password']);
        return $result;
    }

    public function registerUser(string $email, string $password, string $fullname, string $role)
    {

    }
}