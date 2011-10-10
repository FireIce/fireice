<?php

namespace fireice\FireiceSiteTree\Dialogs\DialogsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use fireice\FireiceSiteTree\Dialogs\DialogsBundle\Model\RightsModel;

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
            $modules = $rights_model->getModules($this->get('request')->get('id'));

            $response = new Response(json_encode(array (
                        'node_title' => $node_title,
                        'modules' => $modules
                    )));
        } else {
            $response = new Response(json_encode('error'));
        }

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getUsersAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $acl = $this->get('acl');
        $container = $this->container;

        $rights_model = new RightsModel($em, $acl, $container);

        $users = $rights_model->getUsers($this->get('request'));

        //return $this->render('RightsBundle:Groups:index.html.twig', array('groups'=>array()));

        $response = new Response(json_encode($users));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getUserAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $acl = $this->get('acl');
        $container = $this->container;

        $rights_model = new RightsModel($em, $acl, $container);

        $user = $rights_model->getUser($this->get('request'));

        $response = new Response(json_encode($user));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function editUserAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $acl = $this->get('acl');
        $container = $this->container;

        $rights_model = new RightsModel($em, $acl, $container);

        $rights_model->editUserRights($this->get('request'));

        //print_r($user); exit;
        //return $this->render('RightsBundle:Groups:index.html.twig', array('groups'=>array()));

        $this->get('cache')->updateSiteTreeAccessUser($rights_model->getUserObject($this->get('request')->get('id_user')));

        $response = new Response(json_encode('ok'));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
