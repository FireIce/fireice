<?php

namespace fireice\Backend\Plugins\Uploadimage\Model;

class BackendModel extends \fireice\Backend\Plugins\BasicPlugin\Model\BackendModel
{

    public function getData($sitetree_id, $module, $module_id, $module_type, $rows=false)
    {
        $query = $this->em->createQuery("
            SELECT 
                ".(($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) ? 'md.row_id,' : '')."
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
            ".(($rows !== false) ? 'AND md.row_id IN ('.implode(',', $rows).')' : '')."
            AND m_l.up_tree = ".$sitetree_id."
            AND m_l.up_module = ".$module_id."
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd

            AND md.plugin_id = plg.id_group
            AND md.plugin_type = '".$this->controller->getValue('type')."'");

        $result = $query->getScalarResult();

        $return = array ();
        $plugins = array ();

        foreach ($result as $val) {
            if (!isset($plugins[$val['plugin_name']])) $plugins[$val['plugin_name']] = array ();

            if ($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) {
                $plugins[$val['plugin_name']][$val['row_id']][$val['plugin_id_data']] = $val;
            } else {
                $plugins[$val['plugin_name']][$val['plugin_id_data']] = $val;
            }
        }

        if ($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) {

            foreach ($plugins as $key => $value) {
                if (!isset($return[$key])) $return[$key] = array ();

                foreach ($value as $k => $v) {

                    $return[$key][$k] = array (
                        'row_id' => $v[0]['row_id'],
                        'plugin_type' => $v[0]['plugin_type'],
                        'plugin_name' => $v[0]['plugin_name'],
                        'show_add_button' => 1,
                        'plugin_value' => array ()
                    );

                    foreach ($v as $k2 => $v2) {
                        $return[$key][$k]['plugin_value'][$v2['plugin_id_data']] = array (
                            'alt' => $v2['plugin_value_alt'],
                            'src' => $v2['plugin_value_src']
                        );
                    }
                }

                $return[$key] = array_values($return[$key]);
            }
        } else {
            foreach ($plugins as $key => $value) {
                $return[$key] = array (
                    'plugin_type' => $value[0]['plugin_type'],
                    'plugin_name' => $value[0]['plugin_name'],
                    'show_add_button' => 1,
                    'plugin_value' => array ()
                );

                foreach ($value as $k => $v) {
                    $return[$key]['plugin_value'][$v['plugin_id_data']] = array (
                        'alt' => $v['plugin_value_alt'],
                        'src' => $v['plugin_value_src']
                    );
                }
            }
        }

        if ($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) {
            $tmp = array ();
            foreach ($return as $key => $value) {
                foreach ($value as $v2) {
                    $tmp[] = $v2;
                }
            }
            return $tmp;
        }

        return array_values($return);
    }

    public function setData($data)
    { 
        $plugin_entity_class = 'fireice\\Backend\\Plugins\\'.ucfirst($this->controller->getValue('type')).'\\Entity\\plugin'.$this->controller->getValue('type');

        $id_group = null;

        foreach ($data as $k => $v) {
            if ($id_group == null) {
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