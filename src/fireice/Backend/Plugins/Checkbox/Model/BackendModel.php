<?php

namespace fireice\Backend\Plugins\Checkbox\Model;

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
            AND m_l.up_tree = ".$sitetree_id."
            AND m_l.up_module = ".$module_id."
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd

            AND md.plugin_id = plg.id_group
            AND md.plugin_type = '".$this->controller->getValue('type')."'");

        $result = $query->getScalarResult();

        $tmp = explode(':', $module);

        $entity = $this->container->getParameter('project_name').'\\Modules\\'.substr($tmp[0], 6, -6).'\\Entity\\'.$tmp[1];
        $entity = new $entity();

        $return = array ();
        $plugins = array ();

        foreach ($result as $val) {
            if (!isset($plugins[$val['plugin_name']])) $plugins[$val['plugin_name']] = array ();

            $plugins[$val['plugin_name']][$val['plugin_id_data']] = $val;
        }

        foreach ($entity->getConfig() as $val) {
            if ($val['type'] === 'checkbox') {
                $choices = $this->getChoices($entity, $val['name']);

                $return[$val['name']] = array (
                    'plugin_type' => $val['type'],
                    'plugin_name' => $val['name'],
                    'status' => 'active',
                    'plugin_value' => array ()
                );

                foreach ($choices as $k => $v) {
                    $return[$val['name']]['plugin_value'][$k] = array (
                        'label' => $v,
                        'value' => (isset($plugins[$val['name']][$k]) ? $plugins[$val['name']][$k]['plugin_value'] : '0')
                    );
                }
            }
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