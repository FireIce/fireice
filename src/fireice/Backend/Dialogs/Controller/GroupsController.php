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

class GroupsController extends Controller
{

    public function getGroupsAction()
    {
        $acl = $this->get('acl');
        $em = $this->get('doctrine.orm.entity_manager');
        $container = $this->container;

        $groups_model = new GroupsModel($em, $acl, $container);

        $modules = $groups_model->getModules();

        $groups = $groups_model->findAll();

        // Для каждой группы узнаем установленные права
        foreach ($groups as &$group) {
            $groups_rights = array ();

            $identy_group = new RoleSecurityIdentity('group_'.$group['gr_id']);

            foreach ($modules as $key => $module) {
                $object_module = new module();
                $object_module->setId($module['id']);

                $groups_rights[$module['name']] = array ();

                foreach ($module['module_object']->getRights() as $right) {
                    if ($acl->checkGroupPermissions($object_module, $identy_group, $acl->getValueMask($right['name']))) {
                        $groups_rights[$module['name']][] = $right['name'];
                    }
                }
            }

            $group['gr_rights'] = $groups_rights;
        }

        $response = new Response(json_encode($groups));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getGroupDataAction()
    {
        $acl = $this->get('acl');
        $em = $this->get('doctrine.orm.entity_manager');
        $container = $this->container;

        $groups_model = new GroupsModel($em, $acl, $container);

        $group_data = $groups_model->getGroupData($this->get('request')->get('id'));

        $response = new Response(json_encode($group_data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function editGroupAction()
    {
        $acl = $this->get('acl');
        $em = $this->get('doctrine.orm.entity_manager');
        $container = $this->container;

        $groups_model = new GroupsModel($em, $acl, $container);

        $groups_model->editGroup($this->get('request'));

        $this->get('cache')->updateSiteTreeAccessGroup($this->get('request')->get('id'));

        $response = new Response(json_encode('ok'));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function addGroupAction()
    {
        $acl = $this->get('acl');
        $em = $this->get('doctrine.orm.entity_manager');
        $container = $this->container;

        $groups_model = new GroupsModel($em, $acl, $container);

        $groups_model->addGroup($this->get('request'));

        $response = new Response(json_encode('ok'));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function deleteGroupAction()
    {
        $acl = $this->get('acl');
        $em = $this->get('doctrine.orm.entity_manager');
        $container = $this->container;

        $groups_model = new GroupsModel($em, $acl, $container);

        $groups_model->deleteGroup($this->get('request')->get('id'), $this->get('cache'));

        $response = new Response(json_encode('ok'));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function loginAction()
    {
        // Если нет 3х групп по умолчанию, то нужно их создать и создать суперпользователя  
        $acl = $this->get('acl');
        $em = $this->get('doctrine.orm.entity_manager');
        $container = $this->container;

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
            $groups_model = new GroupsModel($em, $acl, $container);

            $modules = $groups_model->getModules();

            foreach ($modules as $key => $module) {
                $object = new module();
                $object->setId($module['id']);

                $module_object = $module['module_object'];

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_'.$god_group->getId());
                $builder = new MaskBuilder();
                foreach ($module_object->getDefaultRights('God') as $right) {
                    $builder->add($acl->getValueMask($right));
                }
                $acl->createPermissionsForGroup($object, $group, $builder->get());

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_'.$admins_group->getId());
                $builder = new MaskBuilder();
                foreach ($module_object->getDefaultRights('Administrators') as $right) {
                    $builder->add($acl->getValueMask($right));
                }
                $acl->createPermissionsForGroup($object, $group, $builder->get());

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_'.$users_group->getId());
                $builder = new MaskBuilder();
                foreach ($module_object->getDefaultRights('Users') as $right) {
                    $builder->add($acl->getValueMask($right));
                }
                $acl->createPermissionsForGroup($object, $group, $builder->get());

                $group = new \Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity('group_'.$anonim_group->getId());
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