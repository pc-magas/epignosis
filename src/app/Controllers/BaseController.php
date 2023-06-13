<?php

namespace App\Controllers;

use App\Utils\Url;

class BaseController
{
    public static function homepage($di)
    {
        $session = $di->get('session');
        if(empty($session->user)){
            header('Location: '.Url::getAppUrl('login'));
        }

        echo("Homepage");
    }
    
}