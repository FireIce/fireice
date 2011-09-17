<?php

namespace fireice\FireiceSiteTree\Plugins\UploadimageBundle\Controller;

use fireice\FireiceSiteTree\Plugins\UploadBundle\Model\BackendModel;

class BackendController extends \fireice\FireiceSiteTree\Plugins\BasicPluginBundle\Controller\BackendController
{
    protected $model = '\\fireice\\FireiceSiteTree\\Plugins\\UploadimageBundle\\Model\\BackendModel';
    
    public function getNull()
    {        
        return $this->getValues() + array (
            'value' => array(
                0 => array(
                    'alt' => '', 
                    'src' => ''
                )
            )
        );    
    }    
}