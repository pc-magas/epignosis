<?php

namespace App\Controllers;
use App\Utils\Generic;
use App\Services\VaccationService;
use Carbon\Carbon;

class VaccationController extends BaseController
{

    public function addVaccationPage()
    {
        if(!$this->logedinAsEmployee()){
            http_response_code(403);
            header('Location: '.Generic::getAppUrl(''));
        }

        $twig = $this->getServiceContainer()->get('twig');
        echo $twig->render('save_vaccation.html.twig',[
            'csrf'=>$this->getCsrfToken()
        ]);

        return;
    }

    public function savePendingVaccation()
    {
        if(!$this->logedinAsEmployee()){
            $this->jsonResponse(['msg'=>'User cannot perform this action'],403);
            return;
        }

        if(!isset($_POST['from']) || !isset($_POST['until'])){
            $this->jsonResponse(['msg'=>'Missing date range'],400);
        }

        $from = is_string($_POST['from'])?trim($_POST['from']):null;
        $until = is_string($_POST['until'])?trim($_POST['until']):null;

        if(empty($from) || empty($until)){
            $this->jsonResponse(['msg'=>'Missing date range'],400);
        }

        $comment = !is_string($_POST['comment'])?'':$_POST['comment'];

        $di = $this->getServiceContainer();
        
        $service = $di->get(VaccationService::class);
        $session = $di->get('session');

        try{
            if(!$service->addPendingVaccationRequest((int)$session->user['user_id'], new Carbon($from), new Carbon($until),$comment)){
                $this->jsonResponse(['msg'=>"Save failed"],500);
                return;
            }
        }catch(\InvalidArgumentException $e){
            $this->jsonResponse(['msg'=>$e->getMessage()],400);
            return;
        }   

        $this->jsonResponse(['msg'=>"Save failed"],201);
        return;
    }

    public function list($user_id)
    {

    }

    public function delete($vaccation_id)
    {
        
    }

    public function approveReject($vaccation_id)
    {

    }
}