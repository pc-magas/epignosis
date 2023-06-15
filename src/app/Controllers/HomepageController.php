<?php

namespace App\Controllers;

use App\Utils\Generic;

class HomepageController extends \App\Controllers\BaseController
{
    public function homepage()
    {
        $di = $this->getServiceContainer();
        $session = $di->get('session');
        if(empty($session->user)){
            header('Location: '.Generic::getAppUrl('login'));
        }

        echo("Homepage");
    }
    


}