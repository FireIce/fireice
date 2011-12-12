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
            WHERE msg.send_for = :id
            AND msg.send_from = us.id
            ORDER BY msg.id DESC")->setParameter('id', $security->getToken()->getUser()->getId());

        $result = $query->getResult();

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
            WHERE msg.send_for = :send_for
            AND msg.send_from = us.id
            AND msg.id = :id");

        $query->setParameters(array(
            'send_for' => $security->getToken()->getUser()->getId(),
            'id' => $id
        ));
        
        $result = $query->getOneOrNullResult();

        if ($result !== null) {
            if ($result['is_read'] == 0) {
                $query = $this->em->createQuery("UPDATE TreeBundle:messages msg SET msg.is_read = 1 WHERE msg.id = :id")->setParameter('id', $id);
                $query->getResult();
            }

            unset($result['is_read']);

            return $result;
        }

        return 'error';
    }

    public function deleteMessage($id, $security)
    {
        $query = $this->em->createQuery("
            DELETE 
                TreeBundle:messages msg 
            WHERE msg.id = :id 
            AND msg.send_for = :send_for");
        
        $query->setParameters(array(
            'id' => $id,
            'send_for' => $security->getToken()->getUser()->getId()
        ));
        
        $query->getResult();

        return 'ok';
    }

}
