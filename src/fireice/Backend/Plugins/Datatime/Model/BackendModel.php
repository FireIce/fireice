<?php

namespace fireice\Backend\Plugins\Datatime\Model;

class BackendModel extends \fireice\Backend\Plugins\BasicPlugin\Model\BackendModel
{
    
    public function getData($sitetree_id, $module, $module_id, $module_type, $rows=false)
    {
        $query = $this->em->createQuery("
            SELECT 
                ".(($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) ? 'md.row_id,' : '')."
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
            ".(($rows !== false) ? 'AND md.row_id IN ('.implode(',', $rows).')' : '')."
            AND m_l.up_tree = :up_tree
            AND m_l.up_module = :up_module
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd

            AND md.eid IS NULL
            AND md.plugin_id = plg.id
            AND md.plugin_type = :plugin_type");

        $query->setParameters(array(
            'up_tree' => $sitetree_id,
            'up_module' => $module_id,
            'plugin_type' => $this->controller->getValue('type')
        ));        
        
        $result = $query->getScalarResult();

        foreach ($result as &$val) {
            $val['plugin_value'] = array (
                'data' => $val['plugin_value_data'],
                'time' => $val['plugin_value_time']
            );
        }

        return $result;
    }

}