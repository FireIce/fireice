<?php

namespace fireice\Backend\Modules\Model;

class FrontendModel extends GeneralModel
{

    public function getFrontendData($sitetree_id, $module_id, $params=array ())
    {
        $values = array ();

        foreach ($this->getPlugins() as $plugin) {
            if (!isset($values[$plugin->getValue('type')])) {
                $values[$plugin->getValue('type')] = $plugin->getData($sitetree_id, $this->getBundleName().':'.$this->getEntityName(), $module_id, self::TYPE_ITEM);
            }
        }

        $data = array ();

        foreach ($this->getPlugins() as $plugin) {
            $type = $plugin->getValue('type');

            if (count($values[$type]) > 0) {
                foreach ($values[$type] as $val) {
                    if ($val['plugin_name'] == $plugin->getValue('name')) {

                        // Подмена переменных в шаблоне
                        foreach ($params as $k2 => $v2) {
                            $val['plugin_value'] = str_replace("{% ".$k2." %}", $v2, $val['plugin_value']);
                        }

                        $data[$plugin->getValue('name')] = $plugin->getValues() + array ('value' => $val['plugin_value']);
                        break;
                    }
                }

                if (!isset($data[$plugin->getValue('name')])) $data[$plugin->getValue('name')] = $plugin->getNull();
            } else {
                $data[$plugin->getValue('name')] = $plugin->getValues() + array ('value' => '');
            }
        }

        return array (
            'data' => $data,
        );
    }

}