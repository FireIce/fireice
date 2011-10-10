<?php

namespace fireice\FireiceSiteTree\Plugins\Uploadimage\Controller;

use fireice\FireiceSiteTree\Plugins\Uploadimage\Model\BackendModel;

class BackendController extends \fireice\FireiceSiteTree\Plugins\BasicPlugin\Controller\BackendController
{
    protected $model = '\\fireice\\FireiceSiteTree\\Plugins\\Uploadimage\\Model\\BackendModel';

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