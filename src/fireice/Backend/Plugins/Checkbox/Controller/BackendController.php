<?php

namespace fireice\Backend\Plugins\Checkbox\Controller;

use fireice\Backend\Plugins\Checkbox\Model\BackendModel;

class BackendController extends \fireice\Backend\Plugins\BasicPlugin\Controller\BackendController
{
    protected $model = '\\fireice\\Backend\\Plugins\\Checkbox\\Model\\BackendModel';

    public function getNull()
    {
        $config = $this->getValue('config');

        foreach ($config as $key => &$val) {
            $val = array (
                'label' => $val,
                'value' => '0'
            );
        }

        return $this->getValues() + array ('value' => $config);
    }

}