<?php

namespace App\Controllers;

class BaseController
{
    public static function hello($di)
    {
       var_dump($di->get('twig'));
    }
    
}