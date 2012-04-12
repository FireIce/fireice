<?php

namespace fireice\Backend\Modules\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BackendController extends Controller
{
    protected $model = null;

    public function getModel()
    {
        if (null === $this->model) {
            throw new \RuntimeException('Model must be defined in childs class');
        }

        return new $this->model(
                $this->container,
                $this->get('doctrine.orm.entity_manager')
                
        );
    }

    public function getData($sitetreeId, $language) //Добавить параметр язык
    {
        return $this->getModel()->getBackendData($sitetreeId, $this->get('acl'), $this->get('request')->get('id_module'), $language); //Передавать язык
    }

    public function createEdit()
    {
        $this->getModel()->createEdit($this->get('security.context'), $this->get('acl'));
    }

    public function getHistory()
    {
        return $this->getModel()->getHistory();
    }

    public function ajaxLoad()
    {
        $entity = $this->getModel()->getModuleEntity();

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
