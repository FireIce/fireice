<?php

namespace fireice\Frontend\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use fireice\Frontend\Model\FrontendModel;
use fireice\Backend\Tree\Controller\TreeController;

class FrontendController extends Controller
{
    protected $model = null;

    public function getModel()
    {
        if (null === $this->model) {
            $this->model = new FrontendModel(
                    $this->get('doctrine.orm.entity_manager'),
                    $this->get('acl'),
                    $this->get('cache')
            );
        }

        return $this->model;
    }

    public function indexAction()
    {
        $frontendModel = $this->getModel();

        if ($frontendModel->checkServerBusy()) {
            $response = new Response('Сервер занят', 502);
            $response->headers->set('Content-Type', 'text/html');

            return $response;
        }

        $path = $this->get('request')->getPathInfo();
        $path = trim($path, '/');
        $path = explode('/', $path);
        $language = 'en';
        if ($path === array ('')) {
            $language = 'en';
            return $this->showPage(1, $language, '');
        }

        if ($path !== array ()) {
            for ($i = 0; $i < count($path); $i++) {
                if ($i == 0) $childs = $frontendModel->getChilds(1);
                else $childs = $frontendModel->getChilds($path[$i - 1]);

                $in_childs = $frontendModel->inChilds($path[$i], $childs, $language);

                if ($in_childs === false) {
                    // Если остаток соответствует регулярному выражению модуля предыдущего узла,
                    // то считать адрес корректным, открыть этот узел и передать в модуль этот остаток                    
                    $ostatok = implode('/', array_slice($path, $i));

                    if (!isset($path[$i - 1])) $path[$i - 1] = 1;

                    $tree = new TreeController();
                    $tree->setContainer($this->container);

                    if ($tree->getNodeModule($path[$i - 1], $language)->checkEndOf($ostatok)) return $this->showPage($path[$i - 1], $language, $ostatok);

                    // Страницы не существует...
                    return $this->get404Page();
                }
                else if (!$frontendModel->checkAccess($in_childs)) {
                    // Страницы не существует...
                    return $this->get404Page();
                } else {
                    $path[$i] = $in_childs;
                }
            }

            return $this->showPage($path[$i - 1], $language, '');
        }

        // Страницы не существует...
        return $this->get404Page();
    }

    public function showPage($id_node, $language, $params = '')
    {
        $tree = new TreeController;
        $tree->setContainer($this->container);

        $frontendModel = $this->getModel();

        if ($frontendModel->checkAccess($id_node)) {
            $nodeModules = $frontendModel->getNodeUsersModules($id_node, $language);

            foreach ($nodeModules as $key => $val) {

                $frontend = $tree->getNodeModule($id_node, $language, $key)->frontend($params);

                if ($frontend->isRedirect()) return $frontend;

                $modulesHtml[] = $frontend->getContent();
            }
        } else {
            $modulesHtml['main'] = 'Ошибка!<br>Вы не имеете доступа к этой странице!';
        }

        $menu = array (
            'right' => $frontendModel->getMenu(1),
            'sub' => $frontendModel->getMenu($id_node),
        );

        $navigation = $frontendModel->getNavigation($id_node, $language);

        $currentPage = $navigation[count($navigation) - 1];

        $content = $this->renderView('FrontendBundle:Frontend:index.html.twig', array (
            'modules' => $modulesHtml,
            'menu' => $menu,
            'navigation' => $navigation,
            'current_page' => $currentPage,
            'user' => $frontendModel->getUser()
            ));
        return new Response($this->transformationHtml($content));
    }

    public function get404Page()
    {
        $response = new Response('', 404);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    protected function transformationHtml($html)
    {
        return preg_replace('|^(\s*?)(\S)|m', "$2", $html);
    }

}