<?php

namespace App\Exceptions;

class UserNotFoundException extends \Exception
{
    public function __construct(String $emailOrUserId)
    {
        parent::__construct("User with email Or user Id ${emailOrUserId} is not found");
    }
}