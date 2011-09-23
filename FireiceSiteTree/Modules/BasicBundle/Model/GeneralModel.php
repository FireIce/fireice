<?php

namespace fireice\FireiceSiteTree\Modules\BasicBundle\Model;

use Doctrine\ORM\EntityManager;
use fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\module;

class GeneralModel
{
    const TYPE_ITEM = 0;
    const TYPE_LIST = 1;

    protected $plugins;
    protected $container;
    protected $em;
    protected $bundle_name = 'ModuleTextBundle';
    protected $entity_name = 'moduletext';

    public function __construct($container, $em)
    {
        $this->container = $container;
        $this->em = $em;
    }

    public function getBundleName()
    {
        return $this->bundle_name;
    }
    
    public function getEntityName()
    {
        return $this->entity_name;
    }    

    public function addPlugin($plugin)
    {
        $s = 'fireice\\FireiceSiteTree\\Plugins\\'.ucfirst($plugin['type']).'Bundle\\Controller\\BackendController';

        $o = new $s;
        $o->setContainer($this->container);

        foreach ($plugin as $k => $v) {
            $o->addValue($k, $v);
        }

        $this->plugins[$plugin['name']] = $o;
    }

    // Считывание плагинов из конфига и их добавление
    public function getPlugins()
    {
        if (true === empty($this->plugins)) {
            $module = '\\'.$this->container->getParameter('project_name').'\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
            $module = new $module();

            foreach ($module->getConfig() as $val) {
                $this->addPlugin($val);
            }

            return true === empty($this->plugins) ? null : $this->plugins;
        } else return $this->plugins;
    }

    protected function sort($array)
    {
        $module = '\\'.$this->container->getParameter('project_name').'\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
        $module = new $module();

        if (method_exists($module, 'getConfigSort')) {
            // Если указано по какому плагину сортировать, то сортируем по нему
            $config_sort = $module->getConfigSort();

            $config = $module->getConfig();
            $plugins = $this->getPlugins();

            if (!isset($config[$config_sort['sortBy']])) return array_values($array);

            $tmp = $plugins[$config[$config_sort['sortBy']]['name']];

            if ($config_sort['desc'] === true) $tmp->desc = true;
            else $tmp->desc = false;

            usort($array, array ($tmp, 'cmp'));

            return $array;
        }
        else {
            // По какому плагину сортировать не указано
            // Если есть плагин с 'name' => 'fireice_order', то сортируем по нему
            foreach ($module->getConfig() as $val) {
                if ($val['name'] == 'fireice_order') {
                    $plugins = $this->getPlugins();

                    $tmp = $plugins['fireice_order'];

                    usort($array, array ($tmp, 'cmp'));

                    return $array;
                }
            }
        }

        return array_values($array);
    }

}
