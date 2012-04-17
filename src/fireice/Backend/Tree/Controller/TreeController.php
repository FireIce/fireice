<?php

namespace fireice\Backend\Tree\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use fireice\Backend\Tree\Model\TreeModel;
use fireice\Backend\Dialogs\Entity\module;

class TreeController extends Controller
{
    protected $model = null;

    protected function getModel()
    {
        if (null === $this->model) {
            $em = $this->get('doctrine.orm.entity_manager');
            $sess = $this->get('session');
            $container = $this->container;
            $this->model = new TreeModel($em, $sess, $container);
        }
        return $this->model;
    }

    public function backOfficeAction()
    {
        $messages = $this->getModel()->getNewMessages($this->get('security.context'));

        $response = $this->render('TreeBundle:Tree:backoffice.html.twig', array (
            'messages' => $messages,
            'host' => $this->get('request')->getHost().' : backoffice'
            ));

        return $response;
    }

    public function getParentsAction($id)
    {
        $securityContext = $this->container->get('security.context');
        $userCurrent = $securityContext->getToken()->getUser();

        if (is_object($userCurrent)) {
            $parents = array (
                'list' => $this->getModel()->getChildren($id),
                'user' => $userCurrent->getLogin()
            );
        } else {
            $parents = 'no_user';
        }

        $response = new Response(json_encode($parents));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getShowNodesAction()
    {

        $securityContext = $this->container->get('security.context');
        $userCurrent = $securityContext->getToken()->getUser();

        if (is_object($userCurrent)) {
            $showNodes = $this->getModel()->getShowNodes();
            $nodesList = array ();

            foreach ($showNodes as $val) {
                $childrens = $this->getModel()->getChildren($val);
                if ($childrens !== array ()) {
                    $nodesList[] = $childrens;
                }
            }

            $nodesList = array (
                'list' => $nodesList,
                'user' => $userCurrent->getLogin()
            );
        } else {
            $nodesList = 'no_user';
        }

        $response = new Response(json_encode($nodesList));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getNewNodesAction($id)
    {
        $securityContext = $this->container->get('security.context');
        $userCurrent = $securityContext->getToken()->getUser();

        if (is_object($userCurrent)) {
            $showNodes = $this->getModel()->showNodes($id);
            $nodesList = array ();

            foreach ($showNodes as $val) {
                $childrens = $this->getModel()->getParents($val);
                if ($childrens !== array ()) {
                    $nodesList[] = $childrens;
                }
            }

            $nodesList = array (
                'list' => $nodesList,
                'user' => $userCurrent->getLogin()
            );
        } else {
            $nodesList = 'no_user';
        }

        $response = new Response(json_encode($nodesList));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function contextMenuAction($id)
    {
        $contextMenu = $this->getModel()->contextMenu($id, $this->get('acl'));

        $response = new Response(json_encode($contextMenu));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getModulesAction($id)
    {
        if ($this->get('acl')->checkUserTreePermissions(false, $this->get('acl')->getValueMask('create'))) {
            $answer = array (
                'option' => $this->getModel()->getModules($id),
                'node_title' => $this->getModel()->getNodeTitle($id)
            );
        } else {
            $answer = 'no_rights';
        }
        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function nodeCreateAction()
    {   $languages = $this->container->getParameter('languages');
         if ($this->get('acl')->checkUserTreePermissions(false, $this->get('acl')->getValueMask('create'))) {
            $answer = $this->getModel()->create($this->get('security.context'), $languages);

            $this->get('cache')->updateSiteTreeStructure();
            $this->get('cache')->updateSiteTreeAccessAll();
        } else {
            $answer = 'no_rights';
        }
        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getNodeModulesAction()
    {
        $modules = $this->getModel()->getNodeModules($this->get('request')->get('id'), $this->get('acl'));

        if ($modules === array ()) {
            $answer = 'error';
        } else {
            $nodeTitle = $this->getModel()->getNodeTitle($this->get('request')->get('id'));

            $answer = array (
                'node_title' => $nodeTitle,
                'modules' => array_values($modules)
            );
        }

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function dialogCreateEditAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $acl = $this->get('acl');
        $request = $this->get('request');


        if ($request->get('act') == 'show') {

            $modules = $this->getModel()->getNodeModules($request->get('id'), $acl);

            if (isset($modules[$request->get('id_module')][$request->get('language')])) {
                $moduleAct = '\\project\\Modules\\'.$modules[$request->get('id_module')][$request->get('language')]['directory'].'\\Controller\\BackendController';
                $moduleAct = new $moduleAct();
                $moduleAct->setContainer($this->container);

                $fields = $moduleAct->getData($request->get('id'),$request->get('id_module'), $request->get('language')); //??? Добавить язык из реквеста

            } else $fields = 'no_rights';
        } elseif ($request->get('act') == 'edit') {

            $module = $em->getRepository('DialogsBundle:modules')->findOneBy(array (
                'idd' => $request->get('id_module'),
                'final' => 'Y',
                'status' => 'active'
                ));

            if (($module->getType() === 'user' && $acl->checkUserPermissions($request->get('id'), new module($request->get('id_module')), false, $acl->getValueMask('edit'))) ||
                ($module->getType() === 'sitetree_node' && $acl->checkUserTreePermissions(false, $acl->getValueMask('edit')))) {
                $moduleController = '\\project\\Modules\\'.$module->getName().'\\Controller\\BackendController';

                $moduleAct = new $moduleController();
                $moduleAct->setContainer($this->container);

                $moduleAct->createEdit();

                if ($module->getType() == 'sitetree_node') $this->get('cache')->updateSiteTreeStructure();

                $fields = 'ok';
            } else $fields = 'no_rights';
        } elseif ($request->get('act') == 'get_row') {
            if ($acl->checkUserPermissions($request->get('id'), new module($request->get('id_module')), false, $acl->getValueMask('edit'))) {
                $module = $em->getRepository('DialogsBundle:modules')->findOneBy(array (
                    'idd' => $request->get('id_module'),
                    'final' => 'Y',
                    'status' => 'active'
                    ));

                $moduleAct = '\\project\\Modules\\'.$module->getName().'\\Controller\\BackendController';
                $moduleAct = new $moduleAct();
                $moduleAct->setContainer($this->container);

                $fields = $moduleAct->getRowData($request->get('id'), $request->get('id_module'), $request->get('language'), $request->get('row_id'));
            } else $fields = 'no_rights';
        } elseif ($request->get('act') == 'delete_row') {
            if ($acl->checkUserPermissions($request->get('id'), new module($request->get('id_module')), false, $acl->getValueMask('deleteitem'))) {
                $module = $em->getRepository('DialogsBundle:modules')->findOneBy(array (
                    'idd' => $request->get('id_module'),
                    'final' => 'Y',
                    'status' => 'active'
                    ));

                $moduleAct = '\\project\\Modules\\'.$module->getName().'\\Controller\\BackendController';
                $moduleAct = new $moduleAct();
                $moduleAct->setContainer($this->container);

                $moduleAct->deleteRow();

                $fields = 'ok';
            } else $fields = 'no_rights';
        }

        $response = new Response(json_encode($fields));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function updateOrdersAction()
    {
        $module = $this->get('doctrine.orm.entity_manager')->getRepository('DialogsBundle:modules')->findOneBy(array (
            'idd' => $this->get('request')->get('id_module'),
            'final' => 'Y',
            'status' => 'active'
            ));

        $moduleAct = '\\project\\Modules\\'.$module->getName().'\\Controller\\BackendController';
        $moduleAct = new $moduleAct();
        $moduleAct->setContainer($this->container);

        $moduleAct->updateOrders();

        $response = new Response(json_encode('ok'));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function removeAction()
    {
        if ($this->get('acl')->checkUserTreePermissions(false, MaskBuilder::MASK_DELETE)) {

            $this->getModel()->removeAll($this->get('request')->get('id'), $this->get('security.context'));

            $this->get('cache')->updateSiteTreeStructure();
            $this->get('cache')->updateSiteTreeAccessAll();

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getHistoryAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $module = $em->getRepository('DialogsBundle:modules')->findOneBy(array ('id' => $this->get('request')->get('id_module')));

        $moduleController = '\\project\\Modules\\'.$module->getName().'\\Controller\\BackendController';

        $moduleAct = new $moduleController();
        $moduleAct->setContainer($this->container);

        $history = $moduleAct->getHistory();

        $response = new Response(json_encode($history));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function proveEditorAction()
    {

        $answer = $this->getModel()->proveEditor($this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function proveMainEditorAction()
    {

        $answer = $this->getModel()->proveMainEditor($this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function sendToProveEditorAction()
    {

        $answer = $this->getModel()->sendToProveEditor($this->get('security.context'), $this->get('acl'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function sendToProveMainEditorAction()
    {

        $answer = $this->getModel()->sendToProveMainEditor($this->get('security.context'), $this->get('acl'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function returnWriterAction()
    {

        $answer = $this->getModel()->returnWriter($this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function returnEditorAction()
    {
        $answer = $this->getModel()->returnEditor($this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getNewMessagesAction()
    {
        $answer = $this->getModel()->getNewMessages($this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function hideNodeAction($id)
    {
        if ($this->get('acl')->checkUserTreePermissions(false, $this->get('acl')->getValueMask('hidenodes'))) {

            if ($id != '1') {
                $this->getModel()->hideNode($id, $this->get('security.context'));

                $this->get('cache')->updateSiteTreeAccessAll();
                $this->get('cache')->updateSiteTreeStructure();

                $response = new Response(json_encode('ok'));
            } else $response = new Response(json_encode('error'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function showNodeAction($id)
    {
        if ($this->get('acl')->checkUserTreePermissions(false, $this->get('acl')->getValueMask('shownodes'))) {


            $this->getModel()->showNode($id, $this->get('security.context'));

            $this->get('cache')->updateSiteTreeAccessAll();
            $this->get('cache')->updateSiteTreeStructure();

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getNodeModule($idNode, $idModule = false)
    {
        $this->sitetree = $this->container->get('cache')->getSiteTreeStructure();

        if (false !== $idModule) {
            $module = $this->sitetree['nodes'][$idNode]['user_modules'][$idModule];
        } else {
            foreach ($this->sitetree['nodes'][$idNode]['user_modules'] as $key => $value) {
                $module = $value;
                $idModule = $key;
                break;
            }
        }

        $controller = '\\project\\Modules\\'.$module.'\\Controller\\FrontendController';
        $controller = new $controller($idNode, $idModule);
        $controller->setContainer($this->container);

        return $controller;
    }

    public function ajaxLoadAction()
    {
        $modules = $this->getModel()->getNodeModules($this->get('request')->get('id'), $this->get('acl'));

        if ($modules !== array ()) {
            $moduleAct = '\\project\\Modules\\'.$modules[$this->get('request')->get('id_module')]['directory'].'\\Controller\\BackendController';
            $moduleAct = new $moduleAct();
            $moduleAct->setContainer($this->container);

            $answer = $moduleAct->ajaxLoad();
        } else {
            $answer = 'error';
        }

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    // ==================================================================================================

    public function getDefaultRights($group)
    {
        switch ($group) {
            case 'God':
                $rights = array ('create', 'edit', 'delete', 'editnodesrights', 'shownodes', 'hidenodes', 'seehidenodes',
                    'viewusers', 'edituser', 'deleteuser', 'viewgroups', 'editgroup', 'deletegroup');
                break;
            case 'Administrators':
                $rights = array ('create', 'edit');
                break;
            case 'Users':
                $rights = array ('edit');
                break;
            default:
                $rights = array ();
        }

        return $rights;
    }

    public function getRights()
    {
        return array (
            array ('name' => 'create', 'title' => 'Создание узла'),
            array ('name' => 'edit', 'title' => 'Редактирование узла'),
            array ('name' => 'delete', 'title' => 'Удаление узла'),
            array ('name' => 'editnodesrights', 'title' => 'Правка прав узлов-юзеров'),
            array ('name' => 'shownodes', 'title' => 'Право открывать узлы'),
            array ('name' => 'hidenodes', 'title' => 'Право скрывать узлы'),
            array ('name' => 'seehidenodes', 'title' => 'Право смотреть скрытые узлы во фронтенде'),
            array ('name' => 'viewusers', 'title' => 'Смотреть список юзеров'),
            array ('name' => 'edituser', 'title' => 'Редактировать (добавлять) юзеров'),
            array ('name' => 'deleteuser', 'title' => 'Удалять юзеров'),
            array ('name' => 'viewgroups', 'title' => 'Смотреть список групп'),
            array ('name' => 'editgroup', 'title' => 'Редактировать (добавлять) группы'),
            array ('name' => 'deletegroup', 'title' => 'Удалять группы'),
        );
    }

}