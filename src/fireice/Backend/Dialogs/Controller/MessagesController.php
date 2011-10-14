<?php

namespace fireice\Backend\Dialogs\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use fireice\Backend\Dialogs\Model\MessagesModel;

class MessagesController extends Controller
{

    public function getMessagesAction()
    {
        $rights_model = new MessagesModel($this->get('doctrine.orm.entity_manager'));

        $messages = $rights_model->getMessages($this->get('security.context'));

        $response = new Response(json_encode($messages));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getMessageAction()
    {
        $rights_model = new MessagesModel($this->get('doctrine.orm.entity_manager'));

        $message = $rights_model->getMessage($this->get('request')->get('id'), $this->get('security.context'));

        //print_r($message); exit;

        $response = new Response(json_encode($message));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function deleteMessageAction()
    {
        $rights_model = new MessagesModel($this->get('doctrine.orm.entity_manager'));

        $answer = $rights_model->deleteMessage($this->get('request')->get('id'), $this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
