<?php

namespace fireice\Backend\Modules\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontendController extends Controller
{
    protected $model = null;
    protected $idNode;
    protected $idModule;

    public function __construct($idNode, $idModule)
    {
        $this->idNode = $idNode;
        $this->idModule = $idModule;
    }

    public function getModel()
    {
        if (null === $this->model) {
            throw new \RuntimeException('Model must be defined in childs class');
        }

        return new $this->model($this->container, $this->get('doctrine.orm.entity_manager'));
    }

    public function load($params=array ())
    {
        return $this->getModel()->getFrontendData($this->idNode, $this->idModule, $params);
    }

    public function frontend($params, $data=array())
    {
        $model = $this->getModel();

        $url = str_replace($params, '', trim($this->get('request')->getUri(), '/'));
        $url = trim($url, '/');

        return $this->render($model->getBundleName().':Frontend:index.html.twig', array (
                'data' => $this->load(array ('url' => $url))
            ));
    }

    public function checkEndOf($ostatok)
    {
        foreach ($this->getAvailableEndOf() as $val) {
            if (preg_match($val, $ostatok) === 1) return true;
        }

        return false;
    }

    public function getAvailableEndOf()
    {
        return array ();
    }

}
