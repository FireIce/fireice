<?php

namespace fireice\Backend\Plugins\Selectbox\Model;

class BackendModel extends \fireice\Backend\Plugins\BasicPlugin\Model\BackendModel
{
    protected $plugin_name = 'selectbox';

    public function getData($sitetree_id, $module, $module_id, $module_type, $rows=false)
    {
        $query = $this->em->createQuery("
            SELECT 
                ".(($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) ? 'md.row_id,' : '')."
                md.plugin_type, 
                md.plugin_name,
                plg.value AS plugin_value,
                md.status
            FROM 
                ".$module." md, 
                FireicePlugins".ucfirst($this->controller->getValue('type'))."Bundle:plugin".$this->controller->getValue('type')." plg,
                DialogsBundle:moduleslink m_l,
                DialogsBundle:modulespluginslink mp_l
            WHERE (md.final = 'Y' OR md.final = 'W')
            AND md.eid IS NULL
            ".(($rows !== false) ? 'AND md.row_id IN ('.implode(',', $rows).')' : '')."
            AND m_l.up_tree = :up_tree
            AND m_l.up_module = :up_module
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd

            AND md.plugin_id = plg.id
            AND md.plugin_type = :plugin_type");
        
        $query->setParameters(array(
            'up_tree' => $sitetree_id,
            'up_module' => $module_id,
            'plugin_type' => $this->controller->getValue('type')
        ));         

        $result = $query->getScalarResult();

        $tmp = explode(':', $module);

        $entity = '\\project\\Modules\\'.substr($tmp[0], 6, -6).'\\Entity\\'.$tmp[1];
        $entity = new $entity();

        $return = array ();

        foreach ($result as $key => $val) {
            $return[$key] = array (
                'plugin_type' => $val['plugin_type'],
                'plugin_name' => $val['plugin_name'],
                'status' => $val['status'],
                'plugin_value' => array ()
            );

            if ($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) $return[$key]['row_id'] = $val['row_id'];

            $сhoices = $this->getChoices($entity, $val['plugin_name']);

            if (false !== $сhoices) {
                foreach ($сhoices as $k => $v) {
                    $return[$key]['plugin_value'][$k] = array (
                        'value' => $v,
                        'checked' => ($val['plugin_value'] == $k) ? '1' : '0'
                    );
                }
            } else {
                $return[$key]['plugin_value'] = $val['plugin_value'];
            }
        }

        // Если нет записей в БД, нужно всё равно вернуть записи с label
        if ($return === array() && $rows !== false) {
            foreach ($entity->getConfig() as $plugin) {
                $сhoices = $this->getChoices($entity, $plugin['name']);

                if (false === $сhoices) continue;

                if ($plugin['type'] === $this->plugin_name) {
                    $return[$plugin['name']] = array (
                        'plugin_type' => $plugin['type'],
                        'plugin_name' => $plugin['name'],
                        'status' => 'active',
                        'plugin_value' => array ()
                    );

                    foreach ($сhoices as $key => $val) {
                        $return[$plugin['name']]['plugin_value'][$key] = array (
                            'value' => $val,
                            'checked' => '0'
                        );
                    }
                }
            }
        }

        return array_values($return);
    }

}