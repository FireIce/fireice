<?php

namespace fireice\FireiceSiteTree\Plugins\BasicPluginBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BackendController extends Controller
{
    protected $values;
    protected $model = null;

    public function getModel()
    {
        if (null === $this->model) {
            throw new \RuntimeException('Model must be defined in childs class');
        }

        return new $this->model($this->get('doctrine.orm.entity_manager'), $this, $this->container);
    }

    public function addValue($k, $v)
    {
        $this->values[$k] = $v;
    }

    public function getValue($s)
    {
        return $this->values[$s];
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getName()
    {
        return $this->getValue('name');
    }
    
    public function getData($sitetree_id, $module, $module_id, $module_type, $rows=false)
    {
        return $this->getModel()->getData($sitetree_id, $module, $module_id, $module_type, $rows);
    }

    public function getNull()
    {
        return $this->getValues() + array ('value' => '');
    }

    public function setDataInDb($data)
    {
        return $this->getModel()->setData($data);
    }

    public function cmp($arg1, $arg2)
    {
        $tmp1 = $arg1['data'][$this->getValue('name')]['value'];
        $tmp2 = $arg2['data'][$this->getValue('name')]['value'];

        if (is_numeric($tmp1) && is_numeric($tmp2)) {
            if (intval($tmp1) == intval($tmp2)) {
                return 0;
            }

            if (isset($this->desc) && $this->desc) return (intval($tmp1) > intval($tmp2)) ? -1 : 1;
            else return (intval($tmp1) < intval($tmp2)) ? -1 : 1;
        }
        else {
            if (isset($this->desc) && $this->desc) return strnatcmp($tmp2, $tmp1);
            else return strnatcmp($tmp1, $tmp2);
        }
    }

}