<?php

namespace fireice\FireiceSiteTree\Plugins\Checkbox\Controller;

use fireice\FireiceSiteTree\Plugins\Checkbox\Model\BackendModel;

class BackendController extends \fireice\FireiceSiteTree\Plugins\BasicPlugin\Controller\BackendController
{
    protected $model = '\\fireice\\FireiceSiteTree\\Plugins\\Checkbox\\Model\\BackendModel';

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