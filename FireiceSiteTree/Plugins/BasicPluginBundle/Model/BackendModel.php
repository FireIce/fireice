<?php

namespace fireice\FireiceSiteTree\Plugins\BasicPluginBundle\Model;

class BackendModel
{
    protected $em;
    protected $container;

    public function __construct($em, $controller, $container)
    {
        $this->em = $em;
        $this->controller = $controller;
        $this->container = $container;
    }
    
    public function getData($sitetree_id, $module, $module_id, $module_type, $rows=false)
    {
        $query = $this->em->createQuery("
            SELECT 
                ".(($module_type === \fireice\FireiceSiteTree\Modules\BasicBundle\Model\BackendModel::TYPE_LIST) ? 'md.row_id,' : '')."
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
            AND m_l.up_tree = ".$sitetree_id."
            AND m_l.up_module = ".$module_id."
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd

            AND md.plugin_id = plg.id
            AND md.plugin_type = '".$this->controller->getValue('type')."'");

        return $query->getScalarResult();
    }

    public function setData($data)
    {
        $plugin_entity = 'fireice\\FireiceSiteTree\\Plugins\\'.ucfirst($this->controller->getValue('type')).'Bundle\\Entity\\plugin'.$this->controller->getValue('type');
        $plugin_entity = new $plugin_entity();

        $plugin_entity->setValue($data);

        $this->em->persist($plugin_entity);
        $this->em->flush();

        return $plugin_entity->getId();
    }

    // Сканирует конфиг и выдаёт пункты для плагинов: чекбокс, селектбокс и радиобаттон
    protected function getChoices($entity, $plugin_name, array $options=array ())
    {
        $metod = 'config'.ucfirst($plugin_name);

        // Если нет метода задающего источник, то возвращаем false
        if (!method_exists($entity, $metod)) return false;

        $config = $entity->$metod();

        if ($config['type'] === 'array') {
            // Если пункты заданы массивом
            $сhoices = $config['data'];
        } elseif ($config['type'] === 'node') {
            // Если источник - другой узел
            // Определяем что это за модуль
            $query = $this->em->createQuery("
                SELECT 
                    mds.name, 
                    mds.table_name
                FROM 
                    DialogsBundle:modules mds,
                    DialogsBundle:moduleslink m_l
                WHERE m_l.up_tree = ".$config['data']['id_node']."
                AND m_l.up_module = ".$config['data']['id_module']."
                AND m_l.up_module = mds.idd
                AND mds.final = 'Y'
                AND mds.status = 'active'");

            $result = $query->getOneOrNullResult();

            $entity = $this->container->getParameter('project_name').'\\Modules\\'.$result['name'].'\\Entity\\'.$result['table_name'];
            $entity = new $entity();

            $cnf = $entity->getConfig();
            $plugin_for_title = $cnf[$config['data']['plugin_id_for_title']];

            $query = $this->em->createQuery("
                SELECT 
                    md.row_id, 
                    plg.value
                FROM 
                    ".$result['name'].":".$result['table_name']." md, 
                    FireicePlugins".ucfirst($plugin_for_title['type'])."Bundle:plugin".$plugin_for_title['type']." plg,
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE md.status = 'active'
                AND md.final = 'Y'
                
                AND m_l.up_tree = ".$config['data']['id_node']."
                AND m_l.up_module = ".$config['data']['id_module']."
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd

                AND md.plugin_id = plg.id
                AND md.plugin_name = '".$plugin_for_title['name']."'");

            $result = $query->getScalarResult();

            $сhoices = array ();

            foreach ($result as $val) {
                $сhoices[$val['row_id']] = $val['value'];
            }
        } elseif ($config['type'] === 'ajax') {
            // Если источник типа ajax, то возвращаем false
            return false;
        }


        return $сhoices;
    }

}