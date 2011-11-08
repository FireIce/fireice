<?php

namespace fireice\Backend\Dialogs\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use fireice\Backend\Dialogs\Model\UsersModel;

class UsersController extends Controller
{

    public function getUsersAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('viewusers'))) {
            $users_model = new UsersModel($em, $acl);
            $users = $users_model->getUsers();
            
            $answer = array(
                'list' => $users,
                'edit_right' => $acl->checkUserTreePermissions(false, $acl->getValueMask('edituser')),
                'delete_right' => $acl->checkUserTreePermissions(false, $acl->getValueMask('deleteuser')),
            );
            
        } else {
            $answer = 'no_rights';
        }

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getUserDataAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('edituser'))) {
            $users_model = new UsersModel($em, $acl);
            $answer = $users_model->getUserData($this->get('request')->get('id'));
        } else {
            $answer = 'no_rights';
        }

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function editUserAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('edituser'))) {
            $users_model = new UsersModel($em, $acl);
            $user = $users_model->editUser($this->get('request'));
            
            $this->get('cache')->updateSiteTreeAccessUser($user);

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function addUserAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('edituser'))) {
            $users_model = new UsersModel($em, $acl);
            $user = $users_model->addUser($this->get('request'));

            $this->get('cache')->updateSiteTreeAccessUser($user);

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function deleteUserAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('deleteuser'))) {
            $users_model = new UsersModel($em, $acl);
            $users_model->deleteUser($this->get('request')->get('id'));
            $this->get('cache')->deleteSiteTreeAccessUser($this->get('request')->get('id'));

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
