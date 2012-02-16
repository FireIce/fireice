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

    public function backOfficeAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $messages = $tree_model->getNewMessages($this->get('security.context'));

        $response = $this->render('TreeBundle:Tree:backoffice.html.twig', array (
            'messages' => $messages,
            'host' => $this->get('request')->getHost().' : backoffice'
            ));

        return $response;
    }

    public function getParentsAction($id)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $securityContext = $this->container->get('security.context');
        $userCurrent = $securityContext->getToken()->getUser();

        if (is_object($userCurrent)) {
            $parents = array (
                'list' => $tree_model->getChildren($id),
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
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $securityContext = $this->container->get('security.context');
        $userCurrent = $securityContext->getToken()->getUser();

        if (is_object($userCurrent)) {
            $show_nodes = $tree_model->getShowNodes();
            $nodes_list = array ();

            foreach ($show_nodes as $val) {
                $childrens = $tree_model->getChildren($val);
                if (count($childrens) > 0) {
                    $nodes_list[] = $childrens;
                }
            }

            $nodes_list = array (
                'list' => $nodes_list,
                'user' => $userCurrent->getLogin()
            );
        } else {
            $nodes_list = 'no_user';
        }

        $response = new Response(json_encode($nodes_list));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getNewNodesAction($id)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $securityContext = $this->container->get('security.context');
        $userCurrent = $securityContext->getToken()->getUser();

        if (is_object($userCurrent)) {
            $show_nodes = $tree_model->showNodes($id);
            $nodes_list = array ();

            foreach ($show_nodes as $val) {
                $childrens = $tree_model->getParents($val);
                if (count($childrens) > 0) {
                    $nodes_list[] = $childrens;
                }
            }

            $nodes_list = array (
                'list' => $nodes_list,
                'user' => $userCurrent->getLogin()
            );
        } else {
            $nodes_list = 'no_user';
        }

        $response = new Response(json_encode($nodes_list));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function contextMenuAction($id)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $context_menu = $tree_model->contextMenu($id, $this->get('acl'));

        $response = new Response(json_encode($context_menu));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getModulesAction($id)
    {
        if ($this->get('acl')->checkUserTreePermissions(false, $this->get('acl')->getValueMask('create'))) {
            $em = $this->get('doctrine.orm.entity_manager');
            $sess = $this->get('session');
            $container = $this->container;
            $tree_model = new TreeModel($em, $sess, $container);

            $answer = array (
                'option' => $tree_model->getModules($id),
                'node_title' => $tree_model->getNodeTitle($id)
            );
        } else {
            $answer = 'no_rights';
        }
        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function nodeCreateAction()
    {
        if ($this->get('acl')->checkUserTreePermissions(false, $this->get('acl')->getValueMask('create'))) {
            $em = $this->get('doctrine.orm.entity_manager');
            $sess = $this->get('session');
            $container = $this->container;
            $tree_model = new TreeModel($em, $sess, $container);

            $answer = $tree_model->create($this->get('security.context'));

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
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $modules = $tree_model->getNodeModules($this->get('request')->get('id'), $this->get('acl'));

        if (count($modules) == 0) {
            $answer = 'error';
        } else {
            $node_title = $tree_model->getNodeTitle($this->get('request')->get('id'));

            $answer = array (
                'node_title' => $node_title,
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
        $sess = $this->get('session');
        $acl = $this->get('acl');
        $request = $this->get('request');
        $container = $this->container;

        $tree_model = new TreeModel($em, $sess, $container);

        if ($request->get('act') == 'show') {

            $modules = $tree_model->getNodeModules($request->get('id'), $acl);

            if (isset($modules[$request->get('id_module')])) {
                $module_act = '\\project\\Modules\\'.$modules[$request->get('id_module')]['directory'].'\\Controller\\BackendController';
                $module_act = new $module_act();
                $module_act->setContainer($this->container);

                $fields = $module_act->getData($request->get('id'));
            } else $fields = 'no_rights';
        } elseif ($request->get('act') == 'edit') {

            $module = $em->getRepository('DialogsBundle:modules')->findOneBy(array (
                'idd' => $request->get('id_module'),
                'final' => 'Y',
                'status' => 'active'
                ));

            if (($module->getType() === 'user' && $acl->checkUserPermissions($request->get('id'), new module($request->get('id_module')), false, $acl->getValueMask('edit'))) ||
                ($module->getType() === 'sitetree_node' && $acl->checkUserTreePermissions(false, $acl->getValueMask('edit')))) {
                $module_controller = '\\project\\Modules\\'.$module->getName().'\\Controller\\BackendController';

                $module_act = new $module_controller();
                $module_act->setContainer($this->container);

                $module_act->createEdit();

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

                $module_act = '\\project\\Modules\\'.$module->getName().'\\Controller\\BackendController';
                $module_act = new $module_act();
                $module_act->setContainer($this->container);

                $fields = $module_act->getRowData($request->get('id'), $request->get('id_module'), $request->get('row_id'));
            } else $fields = 'no_rights';
        } elseif ($request->get('act') == 'delete_row') {
            if ($acl->checkUserPermissions($request->get('id'), new module($request->get('id_module')), false, $acl->getValueMask('deleteitem'))) {
                $module = $em->getRepository('DialogsBundle:modules')->findOneBy(array (
                    'idd' => $request->get('id_module'),
                    'final' => 'Y',
                    'status' => 'active'
                    ));

                $module_act = '\\project\\Modules\\'.$module->getName().'\\Controller\\BackendController';
                $module_act = new $module_act();
                $module_act->setContainer($this->container);

                $module_act->deleteRow();

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

        $module_act = '\\project\\Modules\\'.$module->getName().'\\Controller\\BackendController';
        $module_act = new $module_act();
        $module_act->setContainer($this->container);

        $module_act->updateOrders();

        $response = new Response(json_encode('ok'));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function removeAction()
    {
        if ($this->get('acl')->checkUserTreePermissions(false, MaskBuilder::MASK_DELETE)) {
            $em = $this->get('doctrine.orm.entity_manager');
            $sess = $this->get('session');
            $container = $this->container;
            $tree_model = new TreeModel($em, $sess, $container);

            $tree_model->removeAll($this->get('request')->get('id'), $this->get('security.context'));

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
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $module = $em->getRepository('DialogsBundle:modules')->findOneBy(array ('id' => $this->get('request')->get('id_module')));

        $module_controller = '\\project\\Modules\\'.$module->getName().'\\Controller\\BackendController';

        $module_act = new $module_controller();
        $module_act->setContainer($this->container);

        $history = $module_act->getHistory();

        $response = new Response(json_encode($history));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function proveEditorAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $answer = $tree_model->proveEditor($this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function proveMainEditorAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $answer = $tree_model->proveMainEditor($this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function sendToProveEditorAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $answer = $tree_model->sendToProveEditor($this->get('security.context'), $this->get('acl'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function sendToProveMainEditorAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $answer = $tree_model->sendToProveMainEditor($this->get('security.context'), $this->get('acl'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function returnWriterAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $answer = $tree_model->returnWriter($this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function returnEditorAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $answer = $tree_model->returnEditor($this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getNewMessagesAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $answer = $tree_model->getNewMessages($this->get('security.context'));

        $response = new Response(json_encode($answer));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function hideNodeAction($id)
    {
        if ($this->get('acl')->checkUserTreePermissions(false, $this->get('acl')->getValueMask('hidenodes'))) {
            $em = $this->get('doctrine.orm.entity_manager');
            $sess = $this->get('session');
            $container = $this->container;
            $tree_model = new TreeModel($em, $sess, $container);

            if ($id != '1') {
                $tree_model->hideNode($id, $this->get('security.context'));

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
            $em = $this->get('doctrine.orm.entity_manager');
            $sess = $this->get('session');
            $container = $this->container;
            $tree_model = new TreeModel($em, $sess, $container);

            $tree_model->showNode($id, $this->get('security.context'));

            $this->get('cache')->updateSiteTreeAccessAll();
            $this->get('cache')->updateSiteTreeStructure();

            $response = new Response(json_encode('ok'));
        } else {
            $response = new Response(json_encode('no_rights'));
        }
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function getNodeModule($id_node, $id_module=false)
    {
        $this->sitetree = $this->container->get('cache')->getSiteTreeStructure();

        if (false !== $id_module) {
            $module = $this->sitetree['nodes'][$id_node]['user_modules'][$id_module];
        } else {
            foreach ($this->sitetree['nodes'][$id_node]['user_modules'] as $key => $value) {
                $module = $value;
                $id_module = $key;
                break;
            }
        }

        $controller = '\\project\\Modules\\'.$module.'\\Controller\\FrontendController';
        $controller = new $controller($id_node, $id_module);
        $controller->setContainer($this->container);

        return $controller;
    }

    public function ajaxLoadAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $sess = $this->get('session');
        $container = $this->container;
        $tree_model = new TreeModel($em, $sess, $container);

        $modules = $tree_model->getNodeModules($this->get('request')->get('id'), $this->get('acl'));

        if (count($modules) > 0) {
            $module_act = '\\project\\Modules\\'.$modules[$this->get('request')->get('id_module')]['directory'].'\\Controller\\BackendController';
            $module_act = new $module_act();
            $module_act->setContainer($this->container);

            $answer = $module_act->ajaxLoad();
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