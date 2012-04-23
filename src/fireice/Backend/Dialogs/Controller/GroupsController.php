<?php

namespace fireice\Backend\Dialogs\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use fireice\Backend\Dialogs\Model\GroupsModel;
use fireice\Backend\Dialogs\Entity\groups;
use fireice\Backend\Dialogs\Entity\module;
use fireice\Backend\Dialogs\Entity\users;
use Symfony\Component\Finder\Finder;
use fireice\Backend\Dialogs\Entity\modules;
use fireice\Backend\Tree\Entity\modulesitetree;
//use Symfony\Component\Yaml\Yaml;
use fireice\Backend\Plugins\Text\Entity\plugintext;
use fireice\Backend\Dialogs\Entity\moduleslink;
use fireice\Backend\Dialogs\Entity\modulespluginslink;

class GroupsController extends Controller
{
    protected $model = null;

    protected function getModel()
    {
        if (null === $this->model) {
            $acl = $this->get('acl');
            $em = $this->get('doctrine.orm.entity_manager');
            $container = $this->container;
            $this->model = new GroupsModel($em, $acl, $container);
        }
        return $this->model;
    }

    public function getGroupsAction()
    {
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('viewgroups'))) {

            $modules = $this->getModel()->getModules();
            $groups = $this->getModel()->findAll();

            // Для каждой группы узнаем установленные права
            foreach ($groups as &$group) {
                $groupsRrights = array ();

                $identyGroup = new RoleSecurityIdentity('group_'.$group['gr_id']);

                foreach ($modules as $key => $module) {
                    $objectModule = new module();
                    $objectModule->setId($module['id']);

                    $groupsRrights[$module['name']] = array ();

                    foreach ($module['module_object']->getRights() as $right) {
                        if ($acl->checkGroupPermissions($objectModule, $identyGroup, $acl->getValueMask($right['name']))) {
                            $groupsRrights[$module['name']][] = $right['name'];
                        }
                    }
                }

                $group['gr_rights'] = $groupsRrights;
            }
            unset($group);

            $answer = array (
                'list' => $groups,
                'edit_right' => $acl->checkUserTreePermissions(false, $acl->getValueMask('editgroup')),
                'delete_right' => $acl->checkUserTreePermissions(false, $acl->getValueMask('deletegroup')),
            );
        } else {
            $answer = 'no_rights';
        }

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getGroupDataAction()
    {
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editgroup'))) {
            $answer = $this->getModel()->getGroupData($this->get('request')->get('id'));
        } else {
            $answer = 'no_rights';
        }
        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function editGroupAction()
    {
        $acl = $this->get('acl');


        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editgroup'))) {
            $this->getModel()->editGroup();

            $this->get('cache')->updateSiteTreeAccessGroup($this->get('request')->get('id'));

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function addGroupAction()
    {
        $acl = $this->get('acl');


        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editgroup'))) {
            $this->getModel()->addGroup();

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function deleteGroupAction()
    {
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('deletegroup'))) {
            $this->getModel()->deleteGroup($this->get('request')->get('id'), $this->get('cache'));

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function loginAction()
    {
        // Если нет 3х групп по умолчанию, то нужно их создать и создать суперпользователя  
        $acl = $this->get('acl');
        $em = $this->get('doctrine.orm.entity_manager');

        $groups = $em->getRepository('DialogsBundle:groups')->findAll();

        if ($groups === array ()) {
            // Создаём группы
            $godGroup = new groups();
            $godGroup->setName('God');
            $godGroup->setTitle('Суперпользователь');
            $em->persist($godGroup);
            $em->flush();

            $adminsGroup = new groups();
            $adminsGroup->setName('Administrators');
            $adminsGroup->setTitle('Администраторы');
            $em->persist($adminsGroup);
            $em->flush();

            $usersGroup = new groups();
            $usersGroup->setName('Users');
            $usersGroup->setTitle('Пользователи');
            $em->persist($usersGroup);
            $em->flush();

            $anonimGroup = new groups();
            $anonimGroup->setName('Anonymous');
            $anonimGroup->setTitle('Анонимные посетители');
            $em->persist($anonimGroup);
            $em->flush();

            // Присваиваем права каждому узлу                                                                                       
            $modules = $this->getModel()->getModules();

            foreach ($modules as $key => $module) {
                $object = new module();
                $object->setId($module['id']);

                $moduleObject = $module['module_object'];

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_'.$godGroup->getId());
                $builder = new MaskBuilder();
                foreach ($moduleObject->getDefaultRights('God') as $right) {
                    $builder->add($acl->getValueMask($right));
                }
                $acl->createPermissionsForGroup($object, $group, $builder->get());

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_'.$adminsGroup->getId());
                $builder = new MaskBuilder();
                foreach ($moduleObject->getDefaultRights('Administrators') as $right) {
                    $builder->add($acl->getValueMask($right));
                }
                $acl->createPermissionsForGroup($object, $group, $builder->get());

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_'.$usersGroup->getId());
                $builder = new MaskBuilder();
                foreach ($moduleObject->getDefaultRights('Users') as $right) {
                    $builder->add($acl->getValueMask($right));
                }
                $acl->createPermissionsForGroup($object, $group, $builder->get());

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_'.$anonimGroup->getId());
                $builder = new MaskBuilder();
                foreach ($moduleObject->getDefaultRights('Anonymous') as $right) {
                    $builder->add($acl->getValueMask($right));
                }
                $acl->createPermissionsForGroup($object, $group, $builder->get());
            }

            // Создаём супер-пользователя
            $superUser = new users();
            $superUser->setLogin('god');
            $superUser->setPassword('god');
            $superUser->setType('no');
            $superUser->setFname('Суперпользователь');
            $superUser->setSname('Суперпользователь');
            $superUser->setEmail('');
            $superUser->setGroups($godGroup->getId());
            $em->persist($superUser);
            $em->flush();

            // Создаём анонимного пользователя
            $anonimUser = new users();
            $anonimUser->setLogin('anonim');
            $anonimUser->setPassword('anonim');
            $anonimUser->setType('anonymous');
            $anonimUser->setFname('Аноним');
            $anonimUser->setSname('Аноним');
            $anonimUser->setEmail('');
            $anonimUser->setGroups($anonimGroup->getId());
            $em->persist($anonimUser);
            $em->flush();

            $message = 'Был зарегистрирован первый пользователь! Логин: god, пароль: god.';

            // --------------------------------------------------------------------------------------------
            // Создаём записи в таблице модулей
            $finder = new Finder();
            $finder->directories()->in($this->container->getParameter('project_modules_directory'))->depth('== 0');

            foreach ($finder as $key => $value) {
                $module = new modules();
                $module->setIdd(0);
                $module->setCid(0);
                $module->setEid(null);
                $module->setFinal('Y');
                if (stripos($value->getFilename(), 'FireiceNode') === false) {
                    $module->setType('user');
                } else {
                    $module->setType('sitetree_node');
                }
                $module->setTableName('module'.strtolower($value->getFilename()));
                $module->setName($value->getFilename());
                $module->setStatus('active');
                $module->setDateCreate(new \DateTime());
                $em->persist($module);
                $em->flush();

                $module->setIdd($module->getId());
                $module->setCid($module->getId());
                $em->persist($module);
                $em->flush();
            }

            // Создали запись в таблице module_sitetree
            $node = new modulesitetree();
            $node->setIdd(0);
            $node->setCid(1);
            $node->setEid(null);
            $node->setFinal('Y');
            $node->setUpParent(null);
            $node->setStatus('active');
            $node->setDateCreate(new \DateTime());
            $em->persist($node);
            $em->flush();

            $node->setIdd($node->getId());
            $em->persist($node);
            $em->flush();

            // Определили какой модуль дерева должен быть привязан к узлу Главной страницы
            $config = $this->container->get('cache')->getModuleConfig('Mainpage');
            $nodeModule = $config['parameters']['modules']['sitetree'];

            // Создадим записи в таблице 
            // Сущность модуля
            $path = '\\project\\Modules\\'.$nodeModule.'\\Entity\\'.'module'.strtolower($nodeModule);

            // Создаём записи в таблице модуля и плагинах
            $text = new plugintext();
            $text->setValue('mainpage');
            $em->persist($text);
            $em->flush();

            $module = new $path;
            $module->setIdd(0);
            $module->setCid(1);
            $module->setEid(null);
            $module->setFinal('Y');
            $module->setPluginId($text->getId());
            $module->setPluginType('text');
            $module->setPluginName('fireice_node_name');
            $module->setStatus('active');
            $module->setDateCreate(new \DateTime());
            $em->persist($module);
            $em->flush();
            $module->setIdd($module->getId());
            $em->persist($module);
            $em->flush();

            $text = new plugintext();
            $text->setValue('Главная страница');
            $em->persist($text);
            $em->flush();

            $module = new $path;
            $module->setIdd(0);
            $module->setCid(1);
            $module->setEid(null);
            $module->setFinal('Y');
            $module->setPluginId($text->getId());
            $module->setPluginType('text');
            $module->setPluginName('fireice_node_title');
            $module->setStatus('active');
            $module->setDateCreate(new \DateTime());
            $em->persist($module);
            $em->flush();
            $module->setIdd($module->getId());
            $em->persist($module);
            $em->flush();

            // Создаём записи в таблице modules_link
            foreach ($config['parameters']['modules'] as $value) {
                $module = $em->getRepository('DialogsBundle:modules')->findOneBy(array (
                    'name' => $value
                    ));

                $moduleslink = new moduleslink();
                $moduleslink->setUpTree(1);
                $moduleslink->setUpModule($module->getId());
                $em->persist($moduleslink);
                $em->flush();

                if (stripos($value, 'FireiceNode') !== false) {

                    $records = $em->getRepository('Module'.$value.'Bundle:module'.strtolower($value))->findAll();

                    foreach ($records as $val) {
                        $modulespluginslink = new modulespluginslink();
                        $modulespluginslink->setUpLink($moduleslink->getId());
                        $modulespluginslink->setUpPlugin($val->getId());
                        $em->persist($modulespluginslink);
                        $em->flush();
                    }
                }
            }
        } else {
            $message = '';
        }

        if ($this->get('request')->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $this->get('request')->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $this->get('request')->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
        }

        return $this->render('DialogsBundle::login.html.twig', array (
                'last_username' => $this->get('request')->getSession()->get(SecurityContext::LAST_USERNAME),
                'error' => $error,
                'message' => $message
            ));
    }

}
