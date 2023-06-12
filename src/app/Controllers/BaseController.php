<?php

namespace App\Controllers;

class BaseController
{
    public static function hello(...$args)
    {
       var_dump($args);
    }
    
}