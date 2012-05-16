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
                    $this->get('cache'),
                    $this->container
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
        $languages = $this->container->getParameter('languages');
        $languageDefault = $languages['default']; //Присвоили по умолчанию
        if ('yes' == $this->container->getParameter('multilanguage')) {
            //берем хост и проверяем есть ли такой хост в конфиге
            $host = $this->get('request')->getHost();
            $languages = $languages['list'];
            foreach ($languages as $lang => $language) {
                $aHosts = $language['host'];
                if (in_array($host, $aHosts)) {
                    $languageDefault = $lang; //присвоили тот на котором прописан Хост
                    break;
                }
            }
        }
        if ($path === array ('')) {
            return $this->showPage(1, $languageDefault, '');
        }
        $isLang = false;
        if ('yes' == $this->container->getParameter('multilanguage')) {
            $languageSelect = $path[0];

            // Проверим есть ли такой язык в списке
            if (array_key_exists($languageSelect, $languages)) {
                // Да это язык
                $isLang = true;
                array_shift($path);
            } else { // Нет, не язык.
                return $this->redirect($languageDefault.$this->get('request')->getPathInfo(), 301);
            }
        } else {
            $languageSelect = $languageDefault;
        }



        if ($path !== array ()) {
            for ($i = 0; $i < count($path); $i++) {
                if ($i == 0) $childs = $frontendModel->getChilds(1);
                else $childs = $frontendModel->getChilds($path[$i - 1]);

                $in_childs = $frontendModel->inChilds($path[$i], $childs, $languageSelect);

                if ($in_childs === false) {
                    // Если остаток соответствует регулярному выражению модуля предыдущего узла,
                    // то считать адрес корректным, открыть этот узел и передать в модуль этот остаток                    
                    $ostatok = implode('/', array_slice($path, $i));

                    if (!isset($path[$i - 1])) $path[$i - 1] = 1;

                    $tree = new TreeController();
                    $tree->setContainer($this->container);

                    if ($tree->getNodeModule($path[$i - 1], $languageSelect)->checkEndOf($ostatok)) return $this->showPage($path[$i - 1], $languageSelect, $ostatok);

                    // Страницы не существует...
                    return $this->get404Page($languageSelect);
                }
                else if (!$frontendModel->checkAccess($in_childs)) {
                    // Страницы не существует...
                    return $this->get404Page($languageSelect);
                } else {
                    $path[$i] = $in_childs;
                }
            }

            return $this->showPage($path[$i - 1], $languageSelect, '');
        }

        // Открыть главную страницу, если только язык в урле
        if ($isLang) return $this->showPage(1, $languageSelect, '');
        // Страницы не существует... 
        return $this->get404Page($languageSelect);
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

    public function get404Page($language = null)
    {
        if (null == $language) {
            $languages = $this->container->getParameter('languages');
            $languageSelected = $languages['default']; //Присвоили по умолчанию
        } else {
            $languageSelected = $language;
        }
        return $this->render('FrontendBundle:Frontend:404.html.twig', array (
                'language' => $languageSelected,
            ));

        /*      $response = new Response('Собственная 404', 404);
          $response->headers->set('Content-Type', 'text/html');

          return $response; */
    }

    protected function transformationHtml($html)
    {
        return preg_replace('|^(\s*?)(\S)|m', "$2", $html);
    }

}