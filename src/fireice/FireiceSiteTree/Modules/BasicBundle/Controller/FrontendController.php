<?php

namespace fireice\FireiceSiteTree\Modules\BasicBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontendController extends Controller
{
    protected $model = null;
    protected $id_node;
    protected $id_module;

    public function __construct($id_node, $id_module)
    {
        $this->id_node = $id_node;
        $this->id_module = $id_module;
    }

    public function getModel()
    {
        if (null === $this->model) {
            throw new \RuntimeException('Model must be defined in childs class');
        }

        return new $this->model($this->container, $this->get('doctrine.orm.entity_manager'), $this->get('request'));
    }

    public function load($params=array ())
    {
        return $this->getModel()->getFrontendData($this->id_node, $this->id_module, $params);
    }

    public function frontend($params)
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
