<?php

namespace fireice\Backend\Dialogs\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use fireice\Backend\Dialogs\Model\MessagesModel;

class MessagesController extends Controller
{
    protected $model = null;

    protected function getModel() 
    {
        if (null === $this->model) {

            $this->model = new MessagesModel($this->get('doctrine.orm.entity_manager'));
        }
        return $this->model;
    }
    
    public function getMessagesAction()
    {
        $messages = $this->getModel()->getMessages($this->get('security.context'));

        $response = new Response(json_encode($messages));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getMessageAction()
    {
        $message = $this->getModel()->getMessage($this->get('request')->get('id'), $this->get('security.context'));

        $response = new Response(json_encode($message));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function deleteMessageAction()
    {
        $answer = $this->getModel()->deleteMessage($this->get('request')->get('id'), $this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
