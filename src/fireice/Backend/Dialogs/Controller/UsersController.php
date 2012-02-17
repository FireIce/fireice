<?php

namespace fireice\Backend\Dialogs\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use fireice\Backend\Dialogs\Model\UsersModel;

class UsersController extends Controller
{
    protected $model = null;

    protected function getModel() 
    {
        if (null === $this->model) {
            $em = $this->get('doctrine.orm.entity_manager');
            $acl = $this->get('acl');
            $this->model = new UsersModel($em, $acl);
        }
        return $this->model;
    }

    public function getUsersAction()
    {
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('viewusers'))) {
            $users_model =  $this->getModel();
            $users = $this->getModel()->getUsers();
            
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
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('edituser'))) {
            $users_model = $this->getModel();
            $answer = $this->getModel()->getUserData($this->get('request')->get('id'));
        } else {
            $answer = 'no_rights';
        }

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function editUserAction()
    {
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('edituser'))) {
            $user = $this->getModel()->editUser();
            
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
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('edituser'))) {
            $user = $this->getModel()->addUser();

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
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('deleteuser'))) {
            $this->getModel()->deleteUser($this->get('request')->get('id'));
            $this->get('cache')->deleteSiteTreeAccessUser($this->get('request')->get('id'));

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
