<?php

namespace App\Utils;

use App\Application;
use Laminas\Session\Container;

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
        $token = substr(base64_encode(random_bytes(100)),0,20);
        $session->csrf = $token;

        return $token;
    }
}