<?php

namespace fireice\Backend\Dialogs\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use fireice\Backend\Dialogs\Model\RightsModel;

class RightsController extends Controller
{

    public function getModulesAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $acl = $this->get('acl');
        $container = $this->container;

        $rights_model = new RightsModel($em, $acl, $container);

        $node_title = $rights_model->getNodeTitle($this->get('request')->get('id'));

        if ($node_title !== false) {
            if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editnodesrights'))) {
                $modules = $rights_model->getModules($this->get('request')->get('id'));

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
        $em = $this->get('doctrine.orm.entity_manager');
        $acl = $this->get('acl');
        $container = $this->container;

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editnodesrights'))) {
            $rights_model = new RightsModel($em, $acl, $container);

            $answer = $rights_model->getUsers();
        } else {
            $answer = 'no_rights';
        }
        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getUserAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $acl = $this->get('acl');
        $container = $this->container;

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editnodesrights'))) {
            $rights_model = new RightsModel($em, $acl, $container);

            $answer = $rights_model->getUser();
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
        $container = $this->container;

        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editnodesrights'))) {
            $rights_model = new RightsModel($em, $acl, $container);
            $rights_model->editUserRights();

            $this->get('cache')->updateSiteTreeAccessUser($rights_model->getUserObject($this->get('request')->get('id_user')));

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
