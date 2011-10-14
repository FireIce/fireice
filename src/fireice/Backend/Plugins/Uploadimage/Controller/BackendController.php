<?php

namespace fireice\Backend\Plugins\Uploadimage\Controller;

use fireice\Backend\Plugins\Uploadimage\Model\BackendModel;

class BackendController extends \fireice\Backend\Plugins\BasicPlugin\Controller\BackendController
{
    protected $model = '\\fireice\\Backend\\Plugins\\Uploadimage\\Model\\BackendModel';

    public function getNull()
    {
        return $this->getValues() + array (
            'value' => array (
                0 => array (
                    'alt' => '',
                    'src' => ''
                )
            )
        );
    }

}