<?php

namespace App\Controllers;

use App\Utils\Generic;

class BaseController
{
    public static function homepage($di)
    {
        $session = $di->get('session');
        if(empty($session->user)){
            header('Location: '.Generic::getAppUrl('login'));
        }

        echo("Homepage");
    }
    
}