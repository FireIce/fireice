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
use Symfony\Component\Yaml\Yaml;
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
                $groups_rights = array();

                $identy_group = new RoleSecurityIdentity('group_' . $group['gr_id']);

                foreach ($modules as $key => $module) {
                    $object_module = new module();
                    $object_module->setId($module['id']);

                    $groups_rights[$module['name']] = array();

                    foreach ($module['module_object']->getRights() as $right) {
                        if ($acl->checkGroupPermissions($object_module, $identy_group, $acl->getValueMask($right['name']))) {
                            $groups_rights[$module['name']][] = $right['name'];
                        }
                    }
                }

                $group['gr_rights'] = $groups_rights;
            }
            unset($group);

            $answer = array(
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

    public function editGroupAction() {
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

        if (count($groups) == 0) {
            // Создаём группы
            $god_group = new groups();
            $god_group->setName('God');
            $god_group->setTitle('Суперпользователь');
            $em->persist($god_group);
            $em->flush();

            $admins_group = new groups();
            $admins_group->setName('Administrators');
            $admins_group->setTitle('Администраторы');
            $em->persist($admins_group);
            $em->flush();

            $users_group = new groups();
            $users_group->setName('Users');
            $users_group->setTitle('Пользователи');
            $em->persist($users_group);
            $em->flush();

            $anonim_group = new groups();
            $anonim_group->setName('Anonymous');
            $anonim_group->setTitle('Анонимные посетители');
            $em->persist($anonim_group);
            $em->flush();

            // Присваиваем права каждому узлу                                                                                       
            $modules = $this->getModel()->getModules();

            foreach ($modules as $key => $module) {
                $object = new module();
                $object->setId($module['id']);

                $module_object = $module['module_object'];

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_' . $god_group->getId());
                $builder = new MaskBuilder();
                foreach ($module_object->getDefaultRights('God') as $right) {
                    $builder->add($acl->getValueMask($right));
                }
                $acl->createPermissionsForGroup($object, $group, $builder->get());

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_' . $admins_group->getId());
                $builder = new MaskBuilder();
                foreach ($module_object->getDefaultRights('Administrators') as $right) {
                    $builder->add($acl->getValueMask($right));
                }
                $acl->createPermissionsForGroup($object, $group, $builder->get());

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_' . $users_group->getId());
                $builder = new MaskBuilder();
                foreach ($module_object->getDefaultRights('Users') as $right) {
                    $builder->add($acl->getValueMask($right));
                }
                $acl->createPermissionsForGroup($object, $group, $builder->get());

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_' . $anonim_group->getId());
                $builder = new MaskBuilder();
                foreach ($module_object->getDefaultRights('Anonymous') as $right) {
                    $builder->add($acl->getValueMask($right));
                }
                $acl->createPermissionsForGroup($object, $group, $builder->get());
            }

            // Создаём супер-пользователя
            $super_user = new users();
            $super_user->setLogin('god');
            $super_user->setPassword('god');
            $super_user->setType('no');
            $super_user->setFname('Суперпользователь');
            $super_user->setSname('Суперпользователь');
            $super_user->setEmail('');
            $super_user->setGroups($god_group->getId());
            $em->persist($super_user);
            $em->flush();

            // Создаём анонимного пользователя
            $anonim_user = new users();
            $anonim_user->setLogin('anonim');
            $anonim_user->setPassword('anonim');
            $anonim_user->setType('anonymous');
            $anonim_user->setFname('Аноним');
            $anonim_user->setSname('Аноним');
            $anonim_user->setEmail('');
            $anonim_user->setGroups($anonim_group->getId());
            $em->persist($anonim_user);
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
                $module->setTableName('module' . strtolower($value->getFilename()));
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
            $config = Yaml::parse($this->container->getParameter('project_modules_directory') . '/Mainpage/Resources/config/config.yml');
            $nodeModule = $config['parameters']['modules']['sitetree'];

            // Создадим записи в таблице 
            // Сущность модуля
            $path = '\\project\\Modules\\' . $nodeModule . '\\Entity\\' . 'module' . strtolower($nodeModule);

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
                $module = $em->getRepository('DialogsBundle:modules')->findOneBy(array(
                    'name' => $value
                        ));

                $moduleslink = new moduleslink();
                $moduleslink->setUpTree(1);
                $moduleslink->setUpModule($module->getId());
                $em->persist($moduleslink);
                $em->flush();

                if (stripos($value, 'FireiceNode') !== false) {

                    $records = $em->getRepository('Module' . $value . 'Bundle:module' . strtolower($value))->findAll();

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

        return $this->render('DialogsBundle::login.html.twig', array(
                    'last_username' => $this->get('request')->getSession()->get(SecurityContext::LAST_USERNAME),
                    'error' => $error,
                    'message' => $message
                ));
    }

}
