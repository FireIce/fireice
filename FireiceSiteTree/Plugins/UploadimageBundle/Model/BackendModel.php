<?php

namespace fireice\FireiceSiteTree\Plugins\UploadimageBundle\Model;

class BackendModel extends \fireice\FireiceSiteTree\Plugins\BasicPluginBundle\Model\BackendModel
{
    public function getFrontendData($sitetree_id, $module, $module_id)
    {
        $query = $this->em->createQuery("
            SELECT 
                md.plugin_type, 
                md.plugin_name,
                plg.id_data AS plugin_id_data,
                plg.alt AS plugin_value_alt,
                plg.src AS plugin_value_src,
                md.status
            FROM 
                ".$module." md, 
                FireicePlugins".ucfirst($this->controller->getValue('type'))."Bundle:plugin".$this->controller->getValue('type')." plg,
                DialogsBundle:moduleslink m_l,
                DialogsBundle:modulespluginslink mp_l
            WHERE md.status = 'active'
            
            AND m_l.up_tree = ".$sitetree_id."
            AND m_l.up_module = ".$module_id."
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd

            AND md.plugin_id = plg.id_group
            AND md.plugin_type = '".$this->controller->getValue('type')."'");

        $result = $query->getScalarResult();
        
        $return = array();
        $plugins = array();
        
        foreach ($result as $val)
        {
            if (!isset($plugins[$val['plugin_name']]))  
                $plugins[$val['plugin_name']] = array();
            
            $plugins[$val['plugin_name']][$val['plugin_id_data']] = $val;
        }         
        
        foreach ($plugins as $key=>$value)
        {
            $return[$key] = array(
                'plugin_type'     => $value[0]['plugin_type'], 
                'plugin_name'     => $value[0]['plugin_name'],
                'plugin_value' => array()                 
            );
            
            foreach ($value as $k=>$v)
            {
                $return[$key]['plugin_value'][$v['plugin_id_data']] = array(
                    'alt' => $v['plugin_value_alt'],
                    'src' => $v['plugin_value_src']                      
                );                   
            }
        }
       
        //print_r($return); exit;
                
        return array_values($return);        
    }      
    
    public function getBackendData($sitetree_id, $module, $module_id, $module_type, $row_id=false)
    {
        $query = $this->em->createQuery("
            SELECT 
                ".(($module_type === \fireice\FireiceSiteTree\Modules\BasicBundle\Model\BackendModel::TYPE_LIST) ? 'md.row_id,' : '')."
                md.plugin_type, 
                md.plugin_name,
                plg.id_data AS plugin_id_data,
                plg.alt AS plugin_value_alt,
                plg.src AS plugin_value_src,
                md.status
            FROM 
                ".$module." md, 
                FireicePlugins".ucfirst($this->controller->getValue('type'))."Bundle:plugin".$this->controller->getValue('type')." plg,
                DialogsBundle:moduleslink m_l,
                DialogsBundle:modulespluginslink mp_l
            WHERE (md.final = 'Y' OR md.final = 'W')
            AND md.eid IS NULL
            ".(($row_id !== false) ? 'AND md.row_id = '.$row_id : '')."
            AND m_l.up_tree = ".$sitetree_id."
            AND m_l.up_module = ".$module_id."
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd

            AND md.plugin_id = plg.id_group
            AND md.plugin_type = '".$this->controller->getValue('type')."'");

        $result = $query->getScalarResult();
        
        $return = array();
        $plugins = array();
        
        foreach ($result as $val)
        {
            if (!isset($plugins[$val['plugin_name']]))  
                $plugins[$val['plugin_name']] = array();
            
            $plugins[$val['plugin_name']][$val['plugin_id_data']] = $val;
        }         
        
        foreach ($plugins as $key=>$value)
        {
            $return[$key] = array(
                'plugin_type'     => $value[0]['plugin_type'], 
                'plugin_name'     => $value[0]['plugin_name'], 
                'show_add_button' => 1,
                'plugin_value' => array()                 
            );
            
            foreach ($value as $k=>$v)
            {
                $return[$key]['plugin_value'][$v['plugin_id_data']] = array(
                    'alt' => $v['plugin_value_alt'],
                    'src' => $v['plugin_value_src']                      
                );                   
            }
        }
       
        //print_r($return); exit;
                
        return array_values($return);        
    }   
    
    public function setData($data)
    {		    	        
        $plugin_entity_class = 'fireice\\FireiceSiteTree\\Plugins\\'.ucfirst($this->controller->getValue('type')).'Bundle\\Entity\\plugin'.$this->controller->getValue('type');
                    
        $id_group = null;
                    
        foreach ($data as $k=>$v)
        {
            if ($id_group == null)
            {
                $plugin_entity = new $plugin_entity_class();
                $plugin_entity->setIdGroup(0);
                $plugin_entity->setIdData($k);
                $plugin_entity->setValue($v); 
                            
			    $this->em->persist($plugin_entity);
                $this->em->flush();	       
                            
                $id_group = $plugin_entity->getId();
                            
                $plugin_entity->setIdGroup($id_group);
                            
			    $this->em->persist($plugin_entity);
                $this->em->flush();	  
                            
                continue;
            }
                        
            $plugin_entity = new $plugin_entity_class();
            $plugin_entity->setIdGroup($id_group);
            $plugin_entity->setIdData($k);
            $plugin_entity->setValue($v);
                        
			$this->em->persist($plugin_entity);
            $this->em->flush();	                         
        }
                    
        return $id_group;  
    }     
}