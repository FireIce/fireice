<?php

namespace fireice\FireiceSiteTree\Plugins\CheckboxBundle\Controller;

use fireice\FireiceSiteTree\Plugins\CheckboxBundle\Model\BackendModel;

class BackendController extends \fireice\FireiceSiteTree\Plugins\BasicPluginBundle\Controller\BackendController
{
    protected $model = '\\fireice\\FireiceSiteTree\\Plugins\\CheckboxBundle\\Model\\BackendModel';

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