<?php

namespace fireice\Backend\Dialogs\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use fireice\Backend\Dialogs\Entity\users;
use fireice\Backend\Dialogs\Entity\UserGroups;
use Symfony\Component\HttpFoundation\Request;

class UsersModel
{
    protected $em;
    protected $acl;

    public function __construct(EntityManager $em, $acl)
    {
        $this->em = $em;
        $this->acl = $acl;
    }

    public function findAll()
    {
        $users = $this->em->getRepository('DialogsBundle:users')->findAll();
        return $users;
    }

    public function getUsers()
    {
        $query = $this->em->createQuery('SELECT us, gr FROM DialogsBundle:users us, DialogsBundle:groups gr 
    	                                 WHERE us.groups=gr.id
                                         ORDER BY us.id');

        $result = $query->getScalarResult();

        return $result;
    }

    public function getPlugins()
    {
        $this->plugins = array ();

        $module = new users();

        $config = $module->getConfig();

        return true === empty($config) ? null : $module->getConfig();
    }

    public function getUserData($userId)
    {
        $query = $this->em->createQuery("SELECT user FROM DialogsBundle:users user 
    	                                 WHERE user.id = :id")->setParameter('id', $userId);

        $query->setMaxResults(1);

        $result = $query->getArrayResult();

        $data = array ();

        $module = new users();

        foreach ($module->getConfig() as $plugin) {
            if ($result !== array()) {
                if ($plugin['name'] != 'groups') $data[$plugin['name']] = $plugin + array ('value' => $result[0][$plugin['name']]);
                else $data[$plugin['name']] = $plugin + array ('value' => $this->getGroups($result[0]['groups']));
            }
            else {
                if ($plugin['name'] != 'groups') $data[$plugin['name']] = $plugin + array ('value' => '');
                else $data[$plugin['name']] = $plugin + array ('value' => $this->getGroups());
            }
        }

        return $data;
    }

    public function getGroups($current = null)
    {
        $groups = $this->em->getRepository('DialogsBundle:groups')->findAll();

        $ret = array ();

        foreach ($groups as $val) {
            $checked = ($current === $val->getId()) ? 1 : 0;

            $ret[$val->getId()] = array (
                'checked' => $checked,
                'value' => $val->getTitle()
            );
        }

        return $ret;
    }

    public function editUser()
    {
        $request = Request::createFromGlobals();
        if (null !== $m = $this->getPlugins()) {
            $user = $this->em->getRepository('DialogsBundle:users')->findOneBy(array ('id' => $request->get('id')));

            foreach ($m as $o) {
                $setMethod = 'set'.ucfirst($o['name']);
                $user->$setMethod($request->get($o['name']));
            }

            $this->em->persist($user);
            $this->em->flush();
            
            return $user;
        }
    }

    public function addUser()
    {
        $request = Request::createFromGlobals();
        if (null !== $m = $this->getPlugins()) {
            $user = new users();

            foreach ($m as $o) {
                $setMethod = 'set'.ucfirst($o['name']);
                $user->$setMethod($request->get($o['name']));
            }

            $this->em->persist($user);
            $this->em->flush();

            return $user;
        }
    }

    public function deleteUser($idUser)
    {
        $user = $this->em->getRepository('DialogsBundle:users')->findOneBy(array ('id' => $idUser));

        $this->em->remove($user);
        $this->em->flush();
    }

}
