<?php

namespace App\Utils;

use App\Application;

class URL
{
    public static function getAppUrl($path)
    {
        $baseUrl = Application::baseUrl();

        $path = trim($path);
        $path = preg_replace('/^\//','',$path);
        
        return filter_var($baseUrl.'/'.$path,FILTER_SANITIZE_URL);
    }
}