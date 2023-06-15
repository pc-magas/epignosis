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
            $token = base64_encode(random_bytes(100));
            $token = str_replace('=','',$token);
            $token = substr($token,0,50);
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