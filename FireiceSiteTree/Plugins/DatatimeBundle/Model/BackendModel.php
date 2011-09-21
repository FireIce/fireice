<?php

namespace fireice\FireiceSiteTree\Plugins\DatatimeBundle\Model;

class BackendModel extends \fireice\FireiceSiteTree\Plugins\BasicPluginBundle\Model\BackendModel
{

    public function getFrontendData($sitetree_id, $module, $module_id)
    {
        $query = $this->em->createQuery("
            SELECT 
                md.plugin_type, 
                md.plugin_name,
                plg.data AS plugin_value_data,
                plg.time AS plugin_value_time
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

            AND md.final = 'Y'
            AND md.plugin_id = plg.id
            AND md.plugin_type = '".$this->controller->getValue('type')."'");

        $result = $query->getScalarResult();

        foreach ($result as &$val) {
            $val['plugin_value'] = array (
                'data' => $val['plugin_value_data'],
                'time' => $val['plugin_value_time']
            );
        }

        //print_r($result); exit;        

        return $result;
    }

    public function getBackendData($sitetree_id, $module, $module_id, $module_type, $row_id=false)
    {
        $query = $this->em->createQuery("
            SELECT 
                ".(($module_type === \fireice\FireiceSiteTree\Modules\BasicBundle\Model\BackendModel::TYPE_LIST) ? 'md.row_id,' : '')."
                md.plugin_type, 
                md.plugin_name,
                plg.data AS plugin_value_data,
                plg.time AS plugin_value_time
            FROM 
                ".$module." md, 
                FireicePlugins".ucfirst($this->controller->getValue('type'))."Bundle:plugin".$this->controller->getValue('type')." plg,
                DialogsBundle:moduleslink m_l,
                DialogsBundle:modulespluginslink mp_l                    
            WHERE (md.final = 'Y' OR md.final = 'W')
            ".(($row_id !== false) ? 'AND md.row_id = '.$row_id : '')."
            AND m_l.up_tree = ".$sitetree_id."
            AND m_l.up_module = ".$module_id."
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd

            AND md.eid IS NULL
            AND md.plugin_id = plg.id
            AND md.plugin_type = '".$this->controller->getValue('type')."'");

        $result = $query->getScalarResult();

        foreach ($result as &$val) {
            $val['plugin_value'] = array (
                'data' => $val['plugin_value_data'],
                'time' => $val['plugin_value_time']
            );
        }

        //print_r($result); exit;        

        return $result;
    }

}