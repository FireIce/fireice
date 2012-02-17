<?php

namespace fireice\Backend\Dialogs\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use fireice\Backend\Dialogs\Model\RightsModel;

class RightsController extends Controller
{
    
    protected $model = null;

    protected function getModel() 
    {
        if (null === $this->model) {
            $em = $this->get('doctrine.orm.entity_manager');
            $acl = $this->get('acl');
            $container = $this->container;
            $this->model = new RightsModel($em, $acl, $container);
        }
        return $this->model;
    }

    public function getModulesAction()
    {
        $acl = $this->get('acl');

        $node_title = $this->getModel()->getNodeTitle($this->get('request')->get('id'));

        if ($node_title !== false) {
            if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editnodesrights'))) {
                $modules = $this->getModel()->getModules($this->get('request')->get('id'));

                $answer = array (
                    'node_title' => $node_title,
                    'modules' => $modules
                );
            } else {
                $answer = 'no_rights';
            }
        } else {
            $answer = 'error';
        }

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getUsersAction()
    {
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editnodesrights'))) {

            $answer = $this->getModel()->getUsers();
        } else {
            $answer = 'no_rights';
        }
        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getUserAction()
    {
        $acl = $this->get('acl');

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editnodesrights'))) {

            $answer = $this->getModel()->getUser();
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

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editnodesrights'))) {
            $this->getModel()->editUserRights();

            $this->get('cache')->updateSiteTreeAccessUser($this->getModel()->getUserObject($this->get('request')->get('id_user')));

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
