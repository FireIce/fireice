<?php

namespace fireice\FireiceSiteTree\Dialogs\DialogsBundle\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\groups;
use fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\module;

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
        $tree_controller = new \fireice\FireiceSiteTree\TreeBundle\Controller\TreeController();

        $return_modules[0] = array (
            'id' => -1,
            'title' => 'Дерево сайта',
            'name' => 'sitetree',
            'rights' => $tree_controller->getRights(),
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
            $config = \Symfony\Component\Yaml\Yaml::parse(
                    $this->container->getParameter('kernel.root_dir')
                    .'//..//src//'
                    .$this->container->getParameter('project_name')
                    .'//Modules//'.$module->getName()
                    .'//Resources//config//config.yml');

            $module_controller = $this->container->getParameter('project_name').'\\Modules\\Module'.ucfirst($config['parameters']['name']).'Bundle\\Controller\\BackendController';

            $module_controller = new $module_controller();

            $return_modules[] = array (
                'id' => $module->getIdd(),
                'title' => $config['parameters']['title'],
                'name' => $config['parameters']['name'],
                'rights' => $module_controller->getRights(),
            );
        }

        return $return_modules;
    }

    public function getGroupData($id)
    {
        $query = $this->em->createQuery("SELECT 
                                             gr 
                                         FROM 
                                             DialogsBundle:groups gr 
    	                                 WHERE gr.id='".$id."'");
        $query->setMaxResults(1);

        $result = $query->getArrayResult();

        $data = array ();

        $entity = 'fireice\\FireiceSiteTree\\Dialogs\\DialogsBundle\\Entity\\groups';
        $entity = new $entity();

        foreach ($entity->getConfig() as $plugin) {
            if (count($result) > 0) {
                $data[$plugin['name']] = $plugin + array ('value' => $result[0][$plugin['name']]);
            } else {
                $data[$plugin['name']] = $plugin + array ('value' => '');
            }
        }

        $modules = $this->getModules();

        if (count($result) > 0) $group = new RoleSecurityIdentity('group_'.$result[0]['id']);

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

                if (count($result) > 0 && $this->acl->checkGroupPermissions($mod, $group, $this->acl->getValueMask($right['name']))) {
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

    public function editGroup($request)
    {
        $group = $this->em->getRepository('DialogsBundle:groups')->findOneBy(array ('id' => $request->get('id')));

        $this->acl->removeGroupPermissions($this->getModules(), new RoleSecurityIdentity('group_'.$group->getId()));

        $group->setName($request->get('name'));
        $group->setTitle($request->get('title'));

        $this->em->persist($group);
        $this->em->flush();

        $modules = $this->getModules();

        $rights = array ();

        $request_array = $request->request->all();

        foreach ($modules as $module) {
            $rights[$module['id']] = array ();

            foreach ($request_array[$module['name']] as $key => $val) {
                if ($val == '1') $rights[$module['id']][] = $key;
            }
        }

        $this->acl->createManyPermissions($rights, new RoleSecurityIdentity('group_'.$group->getId()));
    }

    public function addGroup($request)
    {
        $group = new groups();

        $group->setName($request->get('name'));
        $group->setTitle($request->get('title'));

        $this->em->persist($group);
        $this->em->flush();

        $modules = $this->getModules();

        $rights = array ();

        $request_array = $request->request->all();

        foreach ($modules as $module) {
            $rights[$module['id']] = array ();

            foreach ($request_array[$module['name']] as $key => $val) {
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
