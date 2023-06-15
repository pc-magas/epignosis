<?php

namespace App\Utils;

use App\Application;
use Laminas\Session\Container;

/**
 * Various Miscellanous Utilities
 */
class Generic
{
    public static function getAppUrl($path)
    {
        $baseUrl = Application::baseUrl();

        $path = trim($path);
        $path = preg_replace('/^\//','',$path);
        
        return filter_var($baseUrl.'/'.$path,FILTER_SANITIZE_URL);
    }

    public static function csrf(Container $session)
    {
        if(empty($session->csrf)){
            $token = substr(base64_encode(random_bytes(100)),0,20);
            $session->csrf = $token;
            
        } else {
            $token = $session->csrf;
        }

        return $token;
    }

    public static function validateEmail(string $email)
    {
        if(empty($email)){
            throw new \InvalidArgumentException('Email is empty');
        }


        return filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE?false:true;
    }
}