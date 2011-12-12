<?php

namespace fireice\Backend\Modules\Model;

use Doctrine\ORM\EntityManager;
use fireice\Backend\Dialogs\Entity\module;

class GeneralModel
{
    const TYPE_ITEM = 0;
    const TYPE_LIST = 1;

    protected $plugins;
    protected $container;
    protected $em;
    protected $request;
    protected $module_name = 'text';

    public function __construct($container, $em, $request)
    {
        $this->container = $container;
        $this->em = $em;
        $this->request = $request;
    }

    public function getModuleDir()
    {
        return ucfirst($this->module_name);
    }

    public function getEntityName()
    {
        return 'module'.strtolower($this->module_name);
    }

    public function getBundleName()
    {
        return 'Module'.ucfirst($this->module_name).'Bundle';
    }

    public function getModuleEntity()
    {
        $module = '\\'.$this->container->getParameter('project_name').'\\Modules\\'.$this->getModuleDir().'\\Entity\\'.$this->getEntityName();

        return new $module();
    }

    public function addPlugin($plugin)
    {
        $s = 'fireice\\Backend\\Plugins\\'.ucfirst($plugin['type']).'\\Controller\\BackendController';

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

            $config = $this->getModuleEntity()->getConfig();

            foreach ($config as $val) {
                $this->addPlugin($val);
            }

            return true === empty($this->plugins) ? null : $this->plugins;
        } else return $this->plugins;
    }

    protected function sort($array, $reindex=true)
    {
        $module = $this->getModuleEntity();

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
        } else {
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

        return ($reindex) ? array_values($array) : $array;
    }

}
