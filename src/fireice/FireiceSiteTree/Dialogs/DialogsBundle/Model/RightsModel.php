<?php

namespace fireice\FireiceSiteTree\Dialogs\DialogsBundle\Model;

use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\module;
use fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\aclnodesrights;

class RightsModel
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

    public function getUserObject($id)
    {
        return $this->em->getRepository('DialogsBundle:users')->findOneBy(array ('id' => $id));
    }

    public function getNodeTitle($id)
    {
        $query = $this->em->createQuery("
            SELECT 
                md.idd AS id,
                md.table_name,
                md.name
            FROM 
                TreeBundle:modulesitetree tr,
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.type = 'sitetree_node'
            AND md_l.up_tree = tr.idd
            AND md_l.up_module = md.idd
            AND (tr.status = 'active' OR tr.status = 'hidden')
            AND tr.final = 'Y'
            AND tr.idd=".$id);

        $result = $query->getResult();

        if (count($result) == 0) return false;

        $result = $result[0];

        $query = $this->em->createQuery("
            SELECT 
                stm.plugin_id,
                stm.plugin_type
            FROM 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modulespluginslink md_pl_l,
                Module".$result['name']."Bundle:".$result['table_name']." stm
            WHERE md_l.up_tree = ".$id."
            AND md_l.up_module = ".$result['id']."
            AND md_pl_l.up_link = md_l.id
            AND md_pl_l.up_plugin = stm.idd
            AND stm.plugin_name = 'fireice_node_title'
            AND stm.final = 'Y'
            AND stm.status = 'active'");

        $result = $query->getSingleResult();

        $query = $this->em->createQuery("
            SELECT 
                plg.value
            FROM 
                FireicePlugins".ucfirst($result['plugin_type'])."Bundle:plugin".$result['plugin_type']." plg
            WHERE plg.id = ".$result['plugin_id']);

        $result2 = $query->getSingleResult();

        return $result2['value'];
    }

    public function getModules($id)
    {
        $query = $this->em->createQuery("
            SELECT 
                md.idd AS id,
                md.name AS name,
                md.type AS type
            FROM 
                TreeBundle:modulesitetree tr, 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.type = 'user'
            AND md_l.up_tree = tr.idd
            AND md_l.up_module = md.idd
            AND (tr.status = 'active' OR tr.status = 'hidden')
            AND tr.final = 'Y'
            AND tr.idd=".$id."
            ORDER BY md.type");

        $modules = array ();

        foreach ($query->getResult() as $key => $val) {
            $config = Yaml::parse($this->container->getParameter('project_modules_directory').'//'.$val['name'].'//Resources//config//config.yml');

            $modules[] = array (
                'title' => $config['parameters']['title'],
                'name' => $config['parameters']['name'],
                'id' => $val['id'],
            );
        }

        return $modules;
    }

    public function getUsers($request)
    {
        // Общий запрос все юзеров
        $query = $this->em->createQuery("
            SELECT 
                us.id,
                us.login AS username,
                us.groups AS groupid,                
                gr.title AS grouptitle
            FROM 
                DialogsBundle:users us, 
                DialogsBundle:groups gr
            WHERE us.groups=gr.id");

        $users_result = $query->getResult();

        $users = array ();

        foreach ($users_result as $val) {
            $users[] = $val['id'];
        }

        // Вытаскиваем данные о запрещённых правах для всех юзеров
        $query = $this->em->createQuery("
            SELECT 
                acl.up_user AS id,
                acl.not_rights AS not_rights
            FROM 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:aclnodesrights acl
            WHERE md_l.up_tree = ".$request->get('id_node')."
            AND md_l.up_module = ".$request->get('id_module')."
            AND acl.up_modules_link = md_l.id
            AND acl.up_user IN (".implode(',', $users).")");

        $result = $query->getResult();

        $users_not_rights = array ();

        foreach ($result as $val) {
            $users_not_rights[$val['id']] = $val['not_rights'];
        }

        // Вытаскиваем инфу о модуле        
        $query = $this->em->createQuery("
            SELECT 
                md.name AS name
            FROM 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.idd = ".$request->get('id_module'));

        $module = $query->getSingleResult();

        $module_object = $this->container->getParameter('project_name').'\\Modules\\'.$module['name'].'\\Controller\\BackendController';
        $module_object = new $module_object();

        $groups_rights = array ();
        $users = array ();

        $object_module = new module();
        $object_module->setId($request->get('id_module'));

        // Собираем все вместе
        foreach ($users_result as $val) {
            // Узнаём права группы
            if (!isset($groups_rights['group_'.$val['groupid']])) {
                $identy_group = new RoleSecurityIdentity('group_'.$val['groupid']);

                $groups_rights['group_'.$val['groupid']] = array ();

                foreach ($module_object->getRights() as $right) {
                    if ($this->acl->checkGroupPermissions($object_module, $identy_group, $this->acl->getValueMask($right['name']))) {
                        $groups_rights['group_'.$val['groupid']][] = $right['name'];
                    }
                }
            }

            $user_rights = array ();

            // Проходимся по правам группы и смотрим не выключено ли какое-то право для этого юзера
            foreach ($groups_rights['group_'.$val['groupid']] as $right) {
                $int_right = $this->acl->getValueMask($right);

                if (isset($users_not_rights[$val['id']])) $not_rights = intval($users_not_rights[$val['id']]);
                else $not_rights = 0;

                if (($int_right & (~$not_rights)) === $int_right) {
                    $user_rights[] = $right;
                }
            }

            $users[] = array (
                'id' => $val['id'],
                'user' => $val['username'],
                'group' => $val['grouptitle'],
                'rights' => $user_rights
            );
        }

        //print_r($users); exit;

        return $users;
    }

    public function getUser($request)
    {
        // Вытаскиваем инфу о модуле        
        $query = $this->em->createQuery("
            SELECT 
                md.name AS name
            FROM 
                DialogsBundle:moduleslink md_l,
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.idd = ".$request->get('id_module')."
            AND md_l.up_tree = ".$request->get('id_node')."
            AND md_l.up_module = ".$request->get('id_module'));

        $module = $query->getResult();

        if (count($module) == 0) {
            return 'error';
        }

        $module = $module[0];

        $module_object = $this->container->getParameter('project_name').'\\Modules\\'.$module['name'].'\\Controller\\BackendController';
        $module_object = new $module_object();

        $query = $this->em->createQuery("
            SELECT 
                us.id,
                us.login AS username,
                gr.title AS grouptitle,
                us.groups AS groupid
            FROM 
                DialogsBundle:users us, 
                DialogsBundle:groups gr
            WHERE us.groups=gr.id
            AND us.id = ".$request->get('id_user'));

        $user_result = $query->getSingleResult();

        $query = $this->em->createQuery("
            SELECT 
                acl.not_rights AS not_rights
            FROM 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:aclnodesrights acl
            WHERE md_l.up_tree = ".$request->get('id_node')."
            AND md_l.up_module = ".$request->get('id_module')."
            AND acl.up_modules_link = md_l.id
            AND acl.up_user = ".$request->get('id_user'));

        $notrights_result = $query->getResult();

        $groups_rights = array ();
        $users = array ();

        $object_module = new module();
        $object_module->setId($request->get('id_module'));

        $identy_group = new RoleSecurityIdentity('group_'.$user_result['groupid']);

        $group_rights = array ();

        foreach ($module_object->getRights() as $right) {
            if ($this->acl->checkGroupPermissions($object_module, $identy_group, $this->acl->getValueMask($right['name']))) {
                $group_rights[] = array (
                    'name' => $right['name'],
                    'title' => $right['title']
                );
            }
        }

        $user_rights = array ();

        // Проходимся по правам группы и смотрим не выключено ли какое-то право для этого юзера
        foreach ($group_rights as $right) {
            $int_right = $this->acl->getValueMask($right['name']);

            if (count($notrights_result) > 0) $not_rights = intval($notrights_result[0]['not_rights']);
            else $not_rights = 0;

            if (($int_right & (~$not_rights)) === $int_right) {
                $checked = '1';
            } else {
                $checked = '0';
            }

            $user_rights[$right['name']] = array (
                'label' => $right['title'],
                'value' => $checked
            );
        }

        //print_r($user_rights); exit; 

        return array (
            'user' => $user_result['username'],
            'group' => $user_result['grouptitle'],
            'rights' => array (
                'type' => 'checkbox',
                'name' => 'rights',
                'title' => 'Права пользователя',
                'value' => $user_rights
            )
        );
    }

    public function editUserRights($request)
    {
        // Удаляем старую запись из acl_nodes_not_rights     
        $query = $this->em->createQuery("
            DELETE 
                DialogsBundle:aclnodesrights acl 
            WHERE acl.up_user = ".$request->get('id_user')."
            AND acl.up_modules_link = (
                SELECT 
                    md_l.id
                FROM
                    DialogsBundle:moduleslink md_l
                WHERE md_l.up_tree = ".$request->get('id_node')."
                AND md_l.up_module = ".$request->get('id_module').")");

        $query->getResult();

        // Получаем число в котором включённый бит означает выключенное право
        $builder = new MaskBuilder();

        foreach ($request->get('rights') as $key => $val) {
            if ($val == '0') $builder->add($this->acl->getValueMask($key));
        }

        if ($builder->get() != 0) {
            // Вставляем запись в acl_nodes_not_rights
            $modules_link = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array (
                'up_tree' => $request->get('id_node'),
                'up_module' => $request->get('id_module')
                ));

            $aclnodesrights = new aclnodesrights();
            $aclnodesrights->setUpModulesLink($modules_link->getId());
            $aclnodesrights->setUpUser($request->get('id_user'));
            $aclnodesrights->setNotRights($builder->get());
            $this->em->persist($aclnodesrights);
            $this->em->flush();
        }
    }

}
