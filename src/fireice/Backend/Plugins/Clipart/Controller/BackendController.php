<?php

namespace fireice\Backend\Plugins\Clipart\Controller;

use fireice\Backend\Plugins\Clipart\Model\BackendModel;

class BackendController extends \fireice\Backend\Plugins\BasicPlugin\Controller\BackendController
{
    protected $model = '\\fireice\\Backend\\Plugins\\Clipart\\Model\\BackendModel';

    public function getNull()
    {
        return $this->getValues() + array (
            'value' => array (
                0 => array (
                    'original_src' => '',
                    'original_alt' => '',
                    'big_src' => '',
                    'big_alt' => '',
                    'small_src' => '',
                    'small_alt' => '',
                )
            )
        );
    }

}