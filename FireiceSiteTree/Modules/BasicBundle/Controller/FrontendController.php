<?php

namespace fireice\FireiceSiteTree\Modules\BasicBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FrontendController extends Controller
{
    protected $model = null;

    public function getModel()
    {
        if (null === $this->model) {
            throw new \RuntimeException('Model must be defined in childs class');
        }

        return new $this->model($this->container, $this->get('doctrine.orm.entity_manager'));
    }

    public function frontend($id_node, $module_id)
    {
        $model = $this->getModel();

        return $this->render($model->getBundleName().':Frontend:index.html.twig', array ('data' => $model->getFrontendData($id_node, $module_id)));
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
