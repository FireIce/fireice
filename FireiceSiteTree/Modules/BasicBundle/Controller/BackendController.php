<?php

namespace fireice\FireiceSiteTree\Modules\BasicBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BackendController extends Controller
{
    protected $model = null;

    public function getModel()
    {
        if (null === $this->model) {
            throw new \RuntimeException('Model must be defined in childs class');
        }

        return new $this->model($this->container, $this->get('doctrine.orm.entity_manager'));
    }

    public function getData($sitetree_id)
    {
        return $this->getModel()->getBackendData($sitetree_id, $this->get('acl'), $this->get('request')->get('id_module'));
    }

    public function createEdit()
    {
        $this->getModel()->createEdit($this->get('request'), $this->get('security.context'), $this->get('acl'));
    }

    public function getHistory()
    {
        return $this->getModel()->getHistory($this->get('request'));
    }

    public function ajaxLoad()
    {
        $entity = '\\'.$this->container->getParameter('project_name').'\\Modules\\'.$this->getModel()->getBundleName().'\\Entity\\'.$this->getModel()->getEntityName();
        $entity = new $entity();

        $config = 'config'.ucfirst($this->get('request')->get('plugin'));
        $config = $entity->$config($this->get('request')->get('params'));

        $method = 'ajaxLoad'.ucfirst($config['data']['type']);
        $return = $this->getModel()->$method($config['data']);

        return $return;
    }

    public function getRights()
    {
        return array (
            array ('name' => 'view', 'title' => 'Просмотр'),
            array ('name' => 'edit', 'title' => 'Правка'),
        );
    }

    public function getDefaultRights($group)
    {
        switch ($group) {
            case 'God':
                $rights = array ('edit',);
                break;
            case 'Administrators':
                $rights = array ('edit');
                break;
            case 'Users':
                $rights = array ();
                break;
            default:
                $rights = array ();
        }

        return $rights;
    }

}
