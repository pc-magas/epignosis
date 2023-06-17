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

        $this->jsonResponse(['msg'=>"Save sucess"],201);
        return;
    }

    public function delete()
    {
        if(!$this->logedinAsEmployee()){
            $this->jsonResponse(['msg'=>'User cannot perform this action'],403);
            return;
        }
        
        if(!isset($_POST['token']) || !$this->validateCSRF($_POST['token'])){
            $this->jsonResponse(['msg'=>'Invalid Request'],403);
            return;
        }

        $di = $this->getServiceContainer();

        $service = $di->get(VaccationService::class);

        $session = $di->get('session');
        $user_id = (int)$session->user['user_id'];

        if(!isset($_POST['vaccation_id'])){
            $this->jsonResponse(['msg'=>"VAccation Id has not been provided"],400);
            return;
        }

        $vaccation_id = (int)$_POST['vaccation_id'];

        try{
            if(!$service->delete($vaccation_id,$user_id)){
                $this->jsonResponse(['msg'=>"Delete failed"],500);
                return;
            }
        } catch(\InvalidArgumentException $e) {
            $this->jsonResponse(['msg'=>$e->getMessage()],400);
            return;
        }

        $this->jsonResponse(['msg'=>"Delete sucess"],201);
        return;
    }

    public function list()
    {
        $page = $_GET['page']??1;
        $limit = $_GET['limit']??10;

        $di = $this->getServiceContainer();
        /**
         * @var VaccationService
         */
        $service = $di->get(VaccationService::class);

        if($this->logedinAsManager()){
            $vaccations = $service->list($page,$limit);
        } else if($this->logedinAsEmployee()){
            $session = $di->get('session');
            $user_id = (int)$session->user['user_id'];

            $vaccations = $service->list($page,$limit,$user_id);
        } else {
            http_response_code(403);
            header('Location: '.Generic::getAppUrl(''));
            return;
        }

        $twig = $di->get('twig');

        echo $twig->render('list_vaccations.html.twig',[
            'csrf'=>$this->getCsrfToken(),
            'vaccations'=>$vaccations,
        ]);
    }

    public function approveReject()
    {
        if(!$this->logedinAsManager()){
            http_response_code(403);
            header('Location: '.Generic::getAppUrl(''));
        }
             
        if(!$this->validateCSRF($_POST['token'])){
            $this->jsonResponse(['msg'=>'Invalid Request'],403);
            return;
        }

        if(!isset($_POST['vaccation_id'])){
            $this->jsonResponse(['msg'=>"VAccation Id has not been provided"],400);
            return;
        }

        $vaccation_id = (int)$_POST['vaccation_id'];

        if(!isset($_POST['approval_status']) || empty($_POST['approval_status'])){
            $this->jsonResponse(['msg'=>"Approval Status Has not been provided"],400);
            return;
        }

        $di = $this->getServiceContainer();
        $service = $di->get(VaccationService::class);

        
        if(!$service->changeVaccationStatus($vaccation_id,$_POST['approval_status'])){
            $this->jsonResponse(['msg'=>"Save Failed"],500);
            return;
        }

        $this->jsonResponse(['msg'=>"Save Sucess"],200);
        return;
    }
}