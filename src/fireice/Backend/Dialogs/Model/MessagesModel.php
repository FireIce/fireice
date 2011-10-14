<?php

namespace fireice\Backend\Dialogs\Model;

use Doctrine\ORM\EntityManager;

class MessagesModel
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getMessages($security)
    {
        $query = $this->em->createQuery("
            SELECT 
                msg.id,
                us.login,
                msg.subject,
                msg.date,
                msg.is_read
            FROM 
                TreeBundle:messages msg,
                DialogsBundle:users us
            WHERE msg.send_for = ".$security->getToken()->getUser()->getId()."
            AND msg.send_from = us.id
            ORDER BY msg.id DESC");

        $result = $query->getResult();

        //print_r($result); exit;

        return $result;
    }

    public function getMessage($id, $security)
    {
        $query = $this->em->createQuery("
            SELECT 
                msg.id,
                us.login,
                msg.subject,
                msg.message,
                msg.date,
                msg.is_read
            FROM 
                TreeBundle:messages msg,
                DialogsBundle:users us
            WHERE msg.send_for = ".$security->getToken()->getUser()->getId()."
            AND msg.send_from = us.id
            AND msg.id = ".$id);

        $result = $query->getOneOrNullResult();

        if ($result !== null) {
            if ($result['is_read'] == 0) {
                $query = $this->em->createQuery("UPDATE TreeBundle:messages msg SET msg.is_read = 1 WHERE msg.id = ".$id);
                $query->getResult();
            }

            unset($result['is_read']);

            return $result;
        }

        return 'error';
    }

    public function deleteMessage($id, $security)
    {
        $query = $this->em->createQuery("DELETE TreeBundle:messages msg WHERE msg.id = ".$id." AND msg.send_for = ".$security->getToken()->getUser()->getId());
        $query->getResult();

        return 'ok';
    }

}
