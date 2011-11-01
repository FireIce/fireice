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
        $frontend_model = $this->getModel();

        if ($frontend_model->checkServerBusy()) {
            $response = new Response('Сервер занят', 502);
            $response->headers->set('Content-Type', 'text/html');

            return $response;
        }

        $path = $this->get('request')->getPathInfo();
        $path = trim($path, '/');
        $path = explode('/', $path);

        if (count($path) == 1 && $path[0] === '') {
            return $this->showPage(1, '');
        }

        if (count($path) > 0) {
            for ($i = 0; $i < count($path); $i++) {
                if ($i == 0) $childs = $frontend_model->getChilds(1);
                else $childs = $frontend_model->getChilds($path[$i - 1]);

                $in_childs = $frontend_model->inChilds($path[$i], $childs);

                if ($in_childs === false) {
                    // Если остаток соответствует регулярному выражению модуля предыдущего узла,
                    // то считать адрес корректным, открыть этот узел и передать в модуль этот остаток                    
                    $ostatok = implode('/', array_slice($path, $i));

                    if (!isset($path[$i - 1])) $path[$i - 1] = 1;

                    $tree = new TreeController();
                    $tree->setContainer($this->container);

                    if ($tree->getNodeModule($path[$i - 1])->checkEndOf($ostatok)) return $this->showPage($path[$i - 1], $ostatok);

                    // Страницы не существует...
                    return $this->get404Page();
                }
                else if (!$frontend_model->checkAccess($in_childs)) {
                    // Страницы не существует...
                    return $this->get404Page();
                } else {
                    $path[$i] = $in_childs;
                }
            }

            return $this->showPage($path[$i - 1], '');
        }

        // Страницы не существует...
        return $this->get404Page();
    }

    public function showPage($id_node, $params='')
    {
        $frontend_model = $this->getModel();

        if ($frontend_model->checkAccess($id_node)) {
            $node_modules = $frontend_model->getNodeModules($id_node);

            foreach ($node_modules as $key => $val) {

                $frontend = $tree->getNodeModule($id_node, $key)->frontend($params);

                if ($frontend->isRedirect()) return $frontend;

                $modules_html[] = $frontend->getContent();
            }
        } else {
            $modules_html['main'] = 'Ошибка!<br>Вы не имеете доступа к этой странице!';
        }

        $menu = array (
            'right' => $frontend_model->getMenu(1),
            'sub' => $frontend_model->getMenu($id_node),
        );

        $navigation = $frontend_model->getNavigation($id_node);

        $current_page = $navigation[count($navigation) - 1];

        $content = $this->renderView('FrontendBundle:Frontend:index.html.twig', array (
                'modules' => $modules_html,
                'menu' => $menu,
                'navigation' => $navigation,
                'current_page' => $current_page,
                'user' => $frontend_model->getUser()
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