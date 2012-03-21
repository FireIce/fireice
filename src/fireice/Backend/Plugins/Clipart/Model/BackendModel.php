<?php

namespace fireice\Backend\Plugins\Clipart\Model;

class BackendModel extends \fireice\Backend\Plugins\Uploadimage\Model\BackendModel
{

    public function getData($sitetree_id, $module_id, $language, $moduleEntyty, $module_type, $rows=false)
    {
        $query = $this->em->createQuery("
            SELECT 
                ".(($module_type === \fireice\Backend\Modules\Model\BackendModel::TYPE_LIST) ? 'md.row_id,' : '')."
                md.plugin_type, 
                md.plugin_name,
                plg.id_data AS plugin_id_data,
                plg.original_src AS plugin_value_original_src,
                plg.original_alt AS plugin_value_original_alt,
                plg.big_src AS plugin_value_big_src,
                plg.big_alt AS plugin_value_big_alt,   
                plg.small_src AS plugin_value_small_src,
                plg.small_alt AS plugin_value_small_alt, 
                plg.type_setting as plugin_value_type_setting,
                md.status
            FROM 
                ".$moduleEntyty." md, 
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

            AND md.plugin_id = plg.id_group
            AND md.plugin_type = :plugin_type");
        
        $query->setParameters(array(
            'up_tree' => $sitetree_id,
            'up_module' => $module_id,
            'plugin_type' => $this->controller->getValue('type')
        ));

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
                            'original_src' => $v2['plugin_value_original_src'],
                            'original_alt' => $v2['plugin_value_original_alt'],
                            'big_src' => $v2['plugin_value_big_src'],
                            'big_alt' => $v2['plugin_value_big_alt'],
                            'small_src' => $v2['plugin_value_small_src'],
                            'small_alt' => $v2['plugin_value_small_alt'],
                            'type_setting' => $v2['plugin_value_type_setting']
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
                        'original_src' => $v2['plugin_value_original_src'],
                        'original_alt' => $v2['plugin_value_original_alt'],
                        'big_src' => $v2['plugin_value_big_src'],
                        'big_alt' => $v2['plugin_value_big_alt'],
                        'small_src' => $v2['plugin_value_small_src'],
                        'small_alt' => $v2['plugin_value_small_alt'],
                        'type_setting' => $v2['plugin_value_type_setting']
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

            $this->updateParameters($v);

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

    protected function updateParameters(&$value)
    {
        $value['type_setting'] = trim($value['type_setting']);

        $settings = $this->controller->getSettings();

        if ($value['type_setting'] == 'manually' || $settings === false) {

            // Ручная настройка. Нужно только определить и подставить разрешения (x*y) выбранных картинок
            $info = getimagesize($this->getSrcRealPath($value['original_src']));
            $value['original_x'] = $info[0];
            $value['original_y'] = $info[1];

            $info = getimagesize($this->getSrcRealPath($value['big_src']));
            $value['big_x'] = $info[0];
            $value['big_y'] = $info[1];

            $info = getimagesize($this->getSrcRealPath($value['small_src']));
            $value['small_x'] = $info[0];
            $value['small_y'] = $info[1];
        } elseif ($value['type_setting'] == 'auto' && $settings !== false) {

            // Автоматическая настройка. Нужно определить и подставить разрешение оригинальной картинки.
            // Большую и маленькую получить ресайзом оригинальной (и проставить разрешение для большой и маленькой).
            $info = getimagesize($this->getSrcRealPath($value['original_src']));
            $value['original_x'] = $info[0];
            $value['original_y'] = $info[1];

            if (!isset($settings['resize']['big']['x'])) $settings['resize']['big']['x'] = '*';
            if (!isset($settings['resize']['big']['y'])) $settings['resize']['big']['y'] = '*';
            if (!isset($settings['resize']['small']['x'])) $settings['resize']['small']['x'] = '*';
            if (!isset($settings['resize']['small']['y'])) $settings['resize']['small']['y'] = '*';

            $tmp = $this->resize($value['original_src'], array ('x' => $settings['resize']['big']['x'], 'y' => $settings['resize']['big']['y']));
            $value['big_src'] = $tmp['src'];
            $value['big_x'] = $tmp['size']['x'];
            $value['big_y'] = $tmp['size']['y'];

            $tmp = $this->resize($value['original_src'], array ('x' => $settings['resize']['small']['x'], 'y' => $settings['resize']['small']['y']));
            $value['small_src'] = $tmp['src'];
            $value['small_x'] = $tmp['size']['x'];
            $value['small_y'] = $tmp['size']['y'];
        }
    }

}