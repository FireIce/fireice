<?php

namespace fireice\Backend\Tree\Services;

use Assetic\Cache\FilesystemCache;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use fireice\Backend\Dialogs\Entity\module;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Cache
{
    private $em;
    private $acl;
    private $FilesystemCache;
    private $dirModules;
    private $dirCache;
    private $tmp;
    private $languages;

    public function __construct($em, $acl, $dirCache, $dirModules, $languages)
    {
        $this->FilesystemCache = new FilesystemCache($dirCache);
        $this->dirModules = $dirModules;
        $this->dirCache = $dirCache;
        $this->em = $em;
        $this->acl = $acl;
        $this->languages = $languages;
    }

    public function exists($key)
    {
        return $this->FilesystemCache->has($key);
    }

    public function load($key)
    {
        return unserialize($this->FilesystemCache->get($key));
    }

    public function save($key, $value)
    {
        $this->FilesystemCache->set($key, serialize($value));
    }

    public function delete($key)
    {
        $this->FilesystemCache->remove($key);
    }

    public function getSiteTreeStructure()
    {
        if ($this->exists('sitetree')) {
            return $this->load('sitetree');
        } elseif (!$this->exists('update_sitetree')) {
            $this->save('update_sitetree', 1);

            $sitetree = $this->compileSiteTreeStructure();
            $this->save('sitetree', $sitetree);

            $this->delete('update_sitetree');

            return $sitetree;
        } else {
            return false;
        }
    }

    public function updateSiteTreeStructure()
    {
        $this->save('update_sitetree', 1);
        $this->delete('sitetree');
        $this->save('sitetree', $this->compileSiteTreeStructure());
        $this->delete('update_sitetree');
    }

    public function updateSiteTreeAccessAll()
    {
        $users = $this->em->getRepository('DialogsBundle:users')->findAll();

        foreach ($users as $user) {
            $this->updateSiteTreeAccessUser($user);
        }
    }

    public function updateSiteTreeAccessUser($user = false)
    {
        if ($user === false) {
            $user = $this->acl->current_user;
        }

        $this->save('update_access_'.$user->getId(), 1);

        $access = $this->compileSiteTreeAccess($user);
        $this->save('access_'.$user->getId(), $access);

        $this->delete('update_access_'.$user->getId());
    }

    public function updateSiteTreeAccessGroup($id_group)
    {
        $users = $this->em->getRepository('DialogsBundle:users')->findBy(array ('groups' => $id_group));

        foreach ($users as $user) {
            $this->updateSiteTreeAccessUser($user);
        }
    }

    public function deleteSiteTreeAccessUser($id)
    {
        $this->delete('access_'.$id);
    }

    public function getSiteTreeAccess()
    {
        $user = $this->acl->current_user;

        if ($this->exists('access_'.$user->getId())) {
            return $this->load('access_'.$user->getId());
        } elseif (!$this->exists('update_access_'.$user->getId())) {
            $this->save('update_access_'.$user->getId(), 1);

            $access = $this->compileSiteTreeAccess();
            $this->save('access_'.$user->getId(), $access);

            $this->delete('update_access_'.$user->getId());

            return $access;
        } else {
            return false;
        }
    }

    public function compileSiteTreeAccess($user = false)
    {
        $query = $this->em->createQuery("
            SELECT 
                tr.idd AS id_node,
                tr.status AS status,
                md.idd AS id_module
            FROM 
                TreeBundle:modulesitetree tr, 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md_l.up_tree = tr.idd
            AND md_l.up_module = md.idd
            AND (tr.status = 'active' OR tr.status = 'hidden')
            AND tr.final = 'Y'
            AND md.type='user'");

        $result = $query->getResult();

        $access = array ();

        foreach ($result as $val) {
            $object_module = new module();
            $object_module->setId($val['id_module']);

            if ($val['status'] == 'active') {
                if ($this->acl->checkUserPermissions($val['id_node'], $object_module, $user, MaskBuilder::MASK_VIEW)) {
                    $access[$val['id_node']] = 'true';
                } else {
                    $access[$val['id_node']] = 'false';
                }
            } else {
                if ($this->acl->checkUserTreePermissions($user, $this->acl->getValueMask('seehidenodes'))) {
                    if ($this->acl->checkUserPermissions($val['id_node'], $object_module, $user, MaskBuilder::MASK_VIEW)) {
                        $access[$val['id_node']] = 'true';
                    } else {
                        $access[$val['id_node']] = 'false';
                    }
                } else {
                    $access[$val['id_node']] = 'false';
                }
            }
        }

        return $access;
    }

    private function compileSiteTreeStructure()
    {
        $query = $this->em->createQuery("
            SELECT 
                tr.idd AS node_id,
                tr.up_parent AS up_parent,
                md.table_name AS table,
                md.name AS bundle,
                md.idd AS module_id,
                md.type,
                tr.status,
                md_l.language AS language,
                md_l.is_main AS is_main
            FROM 
                TreeBundle:modulesitetree tr, 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md_l.up_tree = tr.idd
            AND md_l.up_module = md.idd
            AND (tr.status = 'active' OR tr.status = 'hidden')
            AND tr.final = 'Y'");

        $result = $query->getResult();

        $nodes = array ();
        foreach ($result as $key => $val) {
            if (!isset($nodes[$val['node_id']])) $nodes[$val['node_id']] = array (
                    'up_parent' => $val['up_parent'],
                    'sitetree_module' => array (),
                    'user_modules' => array (),
                    'status' => $val['status']
                );

            if ($val['type'] == 'sitetree_node') {
                $nodes[$val['node_id']]['sitetree_module'][$val['language']][$val['module_id']] = array ('name' => $val['bundle'], 'multi' => false);
            }
            if ($val['type'] == 'user') {
                if (!isset($nodes[$val['node_id']])) $nodes[$val['node_id']] = array ();
                $nodes[$val['node_id']]['user_modules'][$val['module_id']] = array ('name' => $val['bundle'], 'multi' => false);
                unset($result[$key]);
            }
        }
//print_r($nodes);
        $this->tmp = $nodes;

        $node_types = array ();

        foreach ($result as $val) {
            if (!isset($node_types[$val['table']])) $node_types[$val['table']] = array (
                    'module_id' => $val['module_id'],
                    'bundle' => $val['bundle'],
                    'ids' => array ()
                );

            $node_types[$val['table']]['ids'][] = $val['node_id'];
        }

        $plugins_values = array ();

        foreach ($node_types as $key => $type) {
            $module = '\\project\\Modules\\'.$type['bundle'].'\\Entity\\'.$key;
            $module = new $module();

            $config = $module->getConfig();

            $plugins = array ();

            foreach ($config as $conf) {
                if (!in_array($conf['type'], $plugins)) $plugins[] = $conf['type'];
            }

            foreach ($plugins as $plugin) {
                $query = $this->em->createQuery("
                    SELECT 
                        m_l.up_tree AS node_id,
                        m_l.language AS language,
                        md.plugin_type, 
                        md.plugin_name,
                        plg
                    FROM 
                        Module".$type['bundle'].'Bundle:'.$key." md, 
                        FireicePlugins".ucfirst($plugin)."Bundle:plugin".$plugin." plg,
                        DialogsBundle:moduleslink m_l,
                        DialogsBundle:modulespluginslink mp_l
                    WHERE md.status = 'active'
            
                    AND m_l.up_tree IN (".implode(',', $type['ids']).")
                    AND m_l.up_module = :id_module
                    AND m_l.id = mp_l.up_link
                    AND mp_l.up_plugin = md.idd

                    AND md.final = 'Y'
                    AND md.plugin_id = plg.id
                    AND md.plugin_type = :plugin_type");

                $query->setParameters(array (
                    'id_module' => $type['module_id'],
                    'plugin_type' => $plugin
                ));

                $plugins_values = array_merge($query->getResult(), $plugins_values);
            }
        }
        $aPlagins = array ();
        foreach ($plugins_values as $value) {
            if (!isset($nodes[$value['node_id']]['plugins'][$value['language']])) $nodes[$value['node_id']]['plugins'][$value['language']] = array ();
            $nodes[$value['node_id']]['plugins'][$value['language']][$value['plugin_name']] = array (
                'type' => $value['plugin_type'],
                'name' => $value['plugin_name'],
                'value' => $value[0]->getValue()
            );
            if (!isset($aPlagins[$value['plugin_name']])) {
                $aPlagins[$value['plugin_name']] = array (
                    'type' => $value['plugin_type'],
                    'name' => $value['plugin_name'],
                    'value' => false);
            }

            if ('fireice_node_name' == $value['plugin_name']) {
                $nodes[$value['node_id']]['path'] = $value[0]->getValue();
            }
        }

        foreach ($nodes as $key => &$node) {
            $path = $this->getPath($key);

            $name_path = array ();

            foreach ($path as $v) {
                if (isset($nodes[$v]['path'])) $name_path[] = $nodes[$v]['path'];
                else $name_path[] = $key;
            }

            $node['url'] = array (
                'id' => implode('/', $path),
                'name' => implode('/', $name_path)
            );
        }

        //Вытяним список языков 
        $languages = $this->languages;
        $languageDefault = $languages['default'];
        $languageAll = $languages['for_all_type_languagest'];
        $languages = $languages['list'];

        // Исключим лишнее
        // Пройдемся по узлам. Прочтем у каждого Конфиг
        foreach ($nodes as $idNode => &$node) {
            // Найдем модуль на основе которого построен узел
            $query = $this->em->createQuery('
                SELECT md.name as name
                FROM 
                    DialogsBundle:moduleslink md_l, 
                    DialogsBundle:modules md
                WHERE
                    md_l.up_tree= :idNode
                    AND md_l.is_main = TRUE
                    AND md_l.up_module = md.idd');
            $query->setParameter('idNode', $idNode);
            $name = $query->getResult();
            $moduleMain = $name[0]['name']; //Это он.
            $config = $this->getModuleConfig($moduleMain);
            $modules = $config['parameters']['modules'];
            /* $aUserModules = array ();
              foreach ($config['parameters']['modules'] as $key => $module) {
              if ('sitetree' != $key) {
              $aUserModules[$key] = $module;
              }
              } */

            foreach ($modules as $key => $module) {
                if ($key == 'sitetree') {
                    if ($module['multilanguage'] == 'yes') {
                        unset($node['sitetree_module'][$languageAll]);
                        unset($node['plugins'][$languageAll]);
                    } else {
                        $tmp = array ();
                        $tmp = $node['sitetree_module'][$languageAll];
                        unset($node['sitetree_module']);
                        $node['sitetree_module'] = array ();
                        $node['sitetree_module'][$languageAll] = $tmp;

                        $tmp = array ();
                        $tmp = $node['plugins'][$languageAll];
                        unset($node['plugins']);
                        $node['plugins'] = array ();
                        $node['plugins'][$languageAll] = $tmp;
                    }
                } else {
                    if ($module['multilanguage'] == 'yes') {
                        foreach ($node['user_modules'] as $id => $mod) {
                            if ($mod['name'] == $module['name']) {
                                $node['user_modules'][$id]['multi'] = true;
                            }
                        }
                    }
                }
            }
        }

        // Проверка плгинов для всех языков
        foreach ($nodes as $key => $val) {
            foreach ($languages as $lang => $language) {
                if (!isset($val['plugins'][$lang])) {
                    $nodes[$key]['plugins'][$lang] = $aPlagins;
                }
            }
            if (!isset($val['plugins'][$languageAll])) {
                $nodes[$key]['plugins'][$languageAll] = $aPlagins;
            }
        }


        $hierarchy = array ();

        foreach ($nodes as $key => $val) {
            $childs = $this->getChilds($key);

            $hierarchy[$key] = $childs !== array () ? $childs : null;
        }
//print_r($nodes);exit;
        return array (
            'nodes' => $nodes,
            'hierarchy' => $hierarchy
        );
    }

    private function getChilds($node_ident)
    {
        $ret = array ();

        foreach ($this->tmp as $key => $val) {
            if ($val['up_parent'] == $node_ident) $ret[] = $key;
        }

        return $ret;
    }

    private function getPath($id)
    {
        if ($id != 1) {
            $return = array ($id);

            if (isset($this->tmp[$id])) {
                $node = $this->tmp[$id];

                $return = array_merge($this->getPath($node['up_parent']), $return);
            }

            return $return;
        } else return array ();
    }

    public function getModuleConfig($name) // Функция чтения файла config для каждого модуля
    {// $name - Наименование Модуля
        $module = 'module_'.$name;
        $configFile = $this->dirModules.$name.'/Resources/config/config.yml';
        $cacheFile = $this->dirCache.'/'.$module;

        if ($this->exists($module) && filemtime($configFile) < filemtime($cacheFile)) {
            // Файл кеша более свежий, поэтому выдаем его
            return $this->load($module);
        } else {
            $config = Yaml::parse($configFile);
            $this->save($module, $config);
            return $config;
        }
    }

}
