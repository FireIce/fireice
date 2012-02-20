<?php

namespace fireice\Backend\Dialogs\Model;

use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use fireice\Backend\Dialogs\Entity\module;
use fireice\Backend\Dialogs\Entity\aclnodesrights;
use Symfony\Component\HttpFoundation\Request;

class RightsModel
{
    protected $em;
    protected $acl;
    protected $container;
    protected $request;

    public function __construct(EntityManager $em, $acl, $container)
    {
        $this->em = $em;
        $this->acl = $acl;
        $this->container = $container;
        $this->request = Request::createFromGlobals();
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
            AND tr.idd = :idd")->setParameter('idd', $id);

        $result = $query->getResult();

        if ($result === array()) return false;

        $result = $result[0];

        $query = $this->em->createQuery("
            SELECT 
                stm.plugin_id,
                stm.plugin_type
            FROM 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modulespluginslink md_pl_l,
                Module".$result['name']."Bundle:".$result['table_name']." stm
            WHERE md_l.up_tree = :up_tree
            AND md_l.up_module = :up_module
            AND md_pl_l.up_link = md_l.id
            AND md_pl_l.up_plugin = stm.idd
            AND stm.plugin_name = 'fireice_node_title'
            AND stm.final = 'Y'
            AND stm.status = 'active'");
        
        $query->setParameters(array(
            'up_tree' => $id,
            'up_module' => $result['id']
        ));

        $result = $query->getSingleResult();

        $query = $this->em->createQuery("
            SELECT 
                plg.value
            FROM 
                FireicePlugins".ucfirst($result['plugin_type'])."Bundle:plugin".$result['plugin_type']." plg
            WHERE plg.id = :id")->setParameter('id', $result['plugin_id']);

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
            AND tr.idd = :idd
            ORDER BY md.type")->setParameter('idd', $id);

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

    public function getUsers()
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
            WHERE us.groups = gr.id");

        $usersResult = $query->getResult();

        $users = array ();

        foreach ($usersResult as $val) {
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
            WHERE md_l.up_tree = :up_tree
            AND md_l.up_module = :up_module
            AND acl.up_modules_link = md_l.id
            AND acl.up_user IN (".implode(',', $users).")");
        
        $query->setParameters(array(
            'up_tree' => $this->request->get('id_node'),
            'up_module' => $this->request->get('id_module')
        ));

        $result = $query->getResult();

        $usersNotRights = array ();

        foreach ($result as $val) {
            $usersNotRights[$val['id']] = $val['not_rights'];
        }

        // Вытаскиваем инфу о модуле        
        $query = $this->em->createQuery("
            SELECT 
                md.name AS name
            FROM 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.idd = :idd")->setParameter('idd', $this->request->get('id_module'));

        $module = $query->getSingleResult();

        $moduleObject = '\\project\\Modules\\'.$module['name'].'\\Controller\\BackendController';
        $moduleObject = new $moduleObject();

        $groupsRights = array ();
        $users = array ();

        $objectModule = new module();
        $objectModule->setId($this->request->get('id_module'));

        // Собираем все вместе
        foreach ($usersResult as $val) {
            // Узнаём права группы
            if (!isset($groupsRights['group_'.$val['groupid']])) {
                $identyGroup = new RoleSecurityIdentity('group_'.$val['groupid']);

                $groupsRights['group_'.$val['groupid']] = array ();

                foreach ($moduleObject->getRights() as $right) {
                    if ($this->acl->checkGroupPermissions($objectModule, $identyGroup, $this->acl->getValueMask($right['name']))) {
                        $groupsRights['group_'.$val['groupid']][] = $right['name'];
                    }
                }
            }

            $userRights = array ();

            // Проходимся по правам группы и смотрим не выключено ли какое-то право для этого юзера
            foreach ($groupsRights['group_'.$val['groupid']] as $right) {
                $intRight = $this->acl->getValueMask($right);

                if (isset($usersNotRights[$val['id']])) $not_rights = intval($usersNotRights[$val['id']]);
                else $not_rights = 0;

                if (($intRight & (~$not_rights)) === $intRight) {
                    $userRights[] = $right;
                }
            }

            $users[] = array (
                'id' => $val['id'],
                'user' => $val['username'],
                'group' => $val['grouptitle'],
                'rights' => $userRights
            );
        }

        return $users;
    }

    public function getUser()
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
            AND md.idd = :idd
            AND md_l.up_tree = :up_tree
            AND md_l.up_module = :up_module");
        
        $query->setParameters(array(
            'idd' => $this->request->get('id_module'),
            'up_tree' => $this->request->get('id_node'),
            'up_module' => $this->request->get('id_module')
        ));

        $module = $query->getResult();

        if ($module === array()) {
            return 'error';
        }

        $module = $module[0];

        $moduleObject = '\\project\\Modules\\'.$module['name'].'\\Controller\\BackendController';
        $moduleObject = new $moduleObject();

        $query = $this->em->createQuery("
            SELECT 
                us.id,
                us.login AS username,
                gr.title AS grouptitle,
                us.groups AS groupid
            FROM 
                DialogsBundle:users us, 
                DialogsBundle:groups gr
            WHERE us.groups = gr.id
            AND us.id = :id")->setParameter('id', $this->request->get('id_user'));

        $userResult = $query->getSingleResult();

        $query = $this->em->createQuery("
            SELECT 
                acl.not_rights AS not_rights
            FROM 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:aclnodesrights acl
            WHERE md_l.up_tree = :up_tree
            AND md_l.up_module = :up_module
            AND acl.up_modules_link = md_l.id
            AND acl.up_user = :up_user");
        
        $query->setParameters(array(
            'up_tree' => $this->request->get('id_node'),
            'up_module' => $this->request->get('id_module'),
            'up_user' => $this->request->get('id_user')
        ));

        $notrightsResult = $query->getResult();

        $groupsRights = array ();
        $users = array ();

        $objectModule = new module();
        $objectModule->setId($this->request->get('id_module'));

        $identyGroup = new RoleSecurityIdentity('group_'.$userResult['groupid']);

        $groupRights = array ();

        foreach ($moduleObject->getRights() as $right) {
            if ($this->acl->checkGroupPermissions($objectModule, $identyGroup, $this->acl->getValueMask($right['name']))) {
                $groupRights[] = array (
                    'name' => $right['name'],
                    'title' => $right['title']
                );
            }
        }

        $userRights = array ();

        // Проходимся по правам группы и смотрим не выключено ли какое-то право для этого юзера
        foreach ($groupRights as $right) {
            $intRight = $this->acl->getValueMask($right['name']);

            if ($notrightsResult !== array()) $not_rights = intval($notrightsResult[0]['not_rights']);
            else $not_rights = 0;

            if (($intRight & (~$not_rights)) === $intRight) {
                $checked = '1';
            } else {
                $checked = '0';
            }

            $userRights[$right['name']] = array (
                'label' => $right['title'],
                'value' => $checked
            );
        }

        return array (
            'user' => $userResult['username'],
            'group' => $userResult['grouptitle'],
            'rights' => array (
                'type' => 'checkbox',
                'name' => 'rights',
                'title' => 'Права пользователя',
                'value' => $userRights
            )
        );
    }

    public function editUserRights()
    {
        // Удаляем старую запись из acl_nodes_not_rights     
        $query = $this->em->createQuery("
            DELETE 
                DialogsBundle:aclnodesrights acl 
            WHERE acl.up_user = :up_user
            AND acl.up_modules_link = (
                SELECT 
                    md_l.id
                FROM
                    DialogsBundle:moduleslink md_l
                WHERE md_l.up_tree = :up_tree
                AND md_l.up_module = :up_module)");
        
        $query->setParameters(array(
            'up_user' => $this->request->get('id_user'),
            'up_tree' => $this->request->get('id_node'),
            'up_module' => $this->request->get('id_module')
        ));

        $query->getResult();

        // Получаем число в котором включённый бит означает выключенное право
        $builder = new MaskBuilder();

        foreach ($this->request->get('rights') as $key => $val) {
            if ($val == '0') $builder->add($this->acl->getValueMask($key));
        }

        if ($builder->get() != 0) {
            // Вставляем запись в acl_nodes_not_rights
            $modules_link = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array (
                'up_tree' => $this->request->get('id_node'),
                'up_module' => $this->request->get('id_module')
                ));

            $aclnodesrights = new aclnodesrights();
            $aclnodesrights->setUpModulesLink($modules_link->getId());
            $aclnodesrights->setUpUser($this->request->get('id_user'));
            $aclnodesrights->setNotRights($builder->get());
            $this->em->persist($aclnodesrights);
            $this->em->flush();
        }
    }

}
