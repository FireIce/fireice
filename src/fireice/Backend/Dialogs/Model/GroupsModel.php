<?php

namespace fireice\Backend\Dialogs\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use fireice\Backend\Dialogs\Entity\groups;
use fireice\Backend\Dialogs\Entity\module;
use fireice\Backend\Tree\Controller\TreeController;
use Symfony\Component\HttpFoundation\Request;

class GroupsModel
{
    protected $em;
    protected $acl;
    protected $container;

    public function __construct(EntityManager $em, $acl, $container)
    {
        $this->em = $em;
        $this->acl = $acl;
        $this->container = $container;
    }

    public function findAll()
    {
        $query = $this->em->createQuery('SELECT gr FROM DialogsBundle:groups gr');

        $result = $query->getScalarResult();

        return $result;
    }

    public function findById($id)
    {
        $group = $this->em->getRepository('DialogsBundle:groups')->findOneBy(array ('id' => $id));

        return $group;
    }

    public function getModules()
    {
        $treeController = new TreeController();

        $returnModules[0] = array (
            'id' => -1,
            'title' => 'Дерево сайта',
            'name' => 'sitetree',
            'rights' => $treeController->getRights(),
            'module_object' => $treeController,
        );

        $query = $this->em->createQuery("
            SELECT 
                md
            FROM 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.type != 'sitetree_node'");

        $modules = $query->getResult();

        foreach ($modules as $module) {
            $config = \Symfony\Component\Yaml\Yaml::parse($this->container->getParameter('project_modules_directory').'//'.$module->getName().'//Resources//config//config.yml');

            $moduleController = '\\project\\Modules\\'.ucfirst($config['parameters']['name']).'\\Controller\\BackendController';

            $moduleController = new $moduleController();

            $moduleObject = '\\project\\Modules\\'.$module->getName().'\\Controller\\BackendController';
            $moduleObject = new $moduleObject();

            $returnModules[] = array (
                'id' => $module->getIdd(),
                'title' => $config['parameters']['title'],
                'name' => $config['parameters']['name'],
                'rights' => $moduleController->getRights(),
                'module_object' => $moduleObject,
            );
        }

        return $returnModules;
    }

    public function getGroupData($id)
    {
        $query = $this->em->createQuery("
            SELECT 
                gr 
            FROM 
                DialogsBundle:groups gr 
    	    WHERE gr.id = :id")->setParameter('id', $id);

        $query->setMaxResults(1);

        $result = $query->getArrayResult();

        $data = array ();

        $entity = new groups();

        foreach ($entity->getConfig() as $plugin) {
            if ($result !== array ()) {
                $data[$plugin['name']] = $plugin + array ('value' => $result[0][$plugin['name']]);
            } else {
                $data[$plugin['name']] = $plugin + array ('value' => '');
            }
        }

        $modules = $this->getModules();

        if ($result !== array ()) $group = new RoleSecurityIdentity('group_'.$result[0]['id']);

        foreach ($modules as $module) {
            $data[$module['name']] = array (
                'type' => 'checkbox',
                'name' => $module['name'],
                'title' => 'Модуль "'.$module['title'].'"',
                'value' => array ()
            );

            foreach ($module['rights'] as $right) {
                $mod = new module();
                $mod->setId($module['id']);

                if ($result !== array () && $this->acl->checkGroupPermissions($mod, $group, $this->acl->getValueMask($right['name']))) {
                    $data[$module['name']]['value'][$right['name']] = array (
                        'label' => $right['title'],
                        'value' => 1
                    );
                } else {
                    $data[$module['name']]['value'][$right['name']] = array (
                        'label' => $right['title'],
                        'value' => 0
                    );
                }
            }
        }

        //print_r($data); exit;

        return $data;
    }

    public function editGroup()
    {
        $request = Request::createFromGlobals();
        $group = $this->em->getRepository('DialogsBundle:groups')->findOneBy(array ('id' => $request->get('id')));

        $this->acl->removeGroupPermissions($this->getModules(), new RoleSecurityIdentity('group_'.$group->getId()));

        $group->setName($request->get('name'));
        $group->setTitle($request->get('title'));

        $this->em->persist($group);
        $this->em->flush();

        $modules = $this->getModules();

        $rights = array ();

        $requestArray = $request->request->all();

        foreach ($modules as $module) {
            $rights[$module['id']] = array ();

            foreach ($requestArray[$module['name']] as $key => $val) {
                if ($val == '1') $rights[$module['id']][] = $key;
            }
        }

        $this->acl->createManyPermissions($rights, new RoleSecurityIdentity('group_'.$group->getId()));
    }

    public function addGroup()
    {
        $request = Request::createFromGlobals();
        $group = new groups();

        $group->setName($request->get('name'));
        $group->setTitle($request->get('title'));

        $this->em->persist($group);
        $this->em->flush();

        $modules = $this->getModules();

        $rights = array ();

        $requestArray = $request->request->all();

        foreach ($modules as $module) {
            $rights[$module['id']] = array ();

            foreach ($requestArray[$module['name']] as $key => $val) {
                if ($val == '1') $rights[$module['id']][] = $key;
            }
        }

        $this->acl->createManyPermissions($rights, new RoleSecurityIdentity('group_'.$group->getId()));
    }

    public function deleteGroup($id, $cache)
    {
        $group = $this->em->getRepository('DialogsBundle:groups')->findOneBy(array ('id' => $id));

        if (is_object($group)) {
            $this->acl->removeGroupPermissions($this->getModules(), new RoleSecurityIdentity('group_'.$group->getId()));

            $this->em->remove($group);
            $this->em->flush();

            $users = $this->em->getRepository('DialogsBundle:users')->findBy(array ('groups' => $id));

            foreach ($users as $user) {
                $cache->deleteSiteTreeAccessUser($user->getId());

                $this->em->remove($user);
                $this->em->flush();
            }

            return true;
        }

        return false;
    }

}
