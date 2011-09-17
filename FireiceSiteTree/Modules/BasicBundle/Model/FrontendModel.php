<?php

namespace fireice\FireiceSiteTree\Modules\BasicBundle\Model;

class FrontendModel extends GeneralModel
{
	public function getFrontendData($sitetree_id, $module_id)
    {        
        $values = array();
        
        foreach ($this->getPlugins() as $plugin)
        {
            if (!isset($values[$plugin->getValue('type')]))       
            {                
                $values[$plugin->getValue('type')] = $plugin->getFrontendModuleData($sitetree_id, $this->bundle_name.':'.$this->entity_name, $module_id);
            }            
        }
        
        $data = array();        
        
        foreach ($this->getPlugins() as $plugin)
        {
            $type = $plugin->getValue('type');
            
            if (count($values[$type]) > 0)
            {
                foreach ($values[$type] as $val)
                {
                    if ($val['plugin_name'] == $plugin->getValue('name'))
                    {
                        $data[$plugin->getValue('name')] = $plugin->getValues() + array('value' => $val['plugin_value']);
                        break;
                    }
                }
                
                if (!isset($data[$plugin->getValue('name')]))
                    $data[$plugin->getValue('name')] = $plugin->getNull();                
                
            } else { $data[$plugin->getValue('name')] = $plugin->getValues() + array('value' => ''); }            
        }

        return array(
            'type' => 'item',
            'data' => $data,
        );           
    }
    
}
