<?php

namespace fireice\FireiceSiteTree\TreeBundle\Services;

use Assetic\Cache\FilesystemCache;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\module;

class Cache
{
    private $em;
    private $acl;
    private $FilesystemCache;
    private $tmp;
    private $project_name;

    public function __construct($em, $acl, $project_name, $cache_dir)
    {
        $this->FilesystemCache = new FilesystemCache($cache_dir);

        $this->em = $em;
        $this->acl = $acl;
        $this->project_name = $project_name;
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

    public function updateSiteTreeAccessUser($user=false)
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

    public function compileSiteTreeAccess($user=false)
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
                $tree_module = new module();
                $tree_module->setId(-1);

                if ($this->acl->checkUserTreePermissions($tree_module, $user, $this->acl->getValueMask('seehidenodes'))) {
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
                md.type
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
                    'user_modules' => array ()
                );

            if ($val['type'] == 'sitetree_node') {
                $nodes[$val['node_id']]['sitetree_module'][$val['module_id']] = $val['bundle'];
            }
            if ($val['type'] == 'user') {
                $nodes[$val['node_id']]['user_modules'][$val['module_id']] = $val['bundle'];
                unset($result[$key]);
            }
        }

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
            $module = '\\'.$this->project_name.'\\Modules\\'.$type['bundle'].'\\Entity\\'.$key;
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
                        md.plugin_type, 
                        md.plugin_name,
                        plg
                    FROM 
                        Module".$type['bundle'].'Bundle:'.$key." md, 
                        FireicePlugins".ucfirst($plugin)."Bundle:plugin".$plugin." plg,
                        DialogsBundle:moduleslink m_l,
                        DialogsBundle:modulespluginslink mp_l
                    WHERE md.status = 'active'
            
                    AND m_l.up_tree In (".implode(',', $type['ids']).")
                    AND m_l.up_module = ".$type['module_id']."
                    AND m_l.id = mp_l.up_link
                    AND mp_l.up_plugin = md.idd

                    AND md.final = 'Y'
                    AND md.plugin_id = plg.id
                    AND md.plugin_type = '".$plugin."'");

                $plugins_values = array_merge($query->getResult(), $plugins_values);
            }
        }

        foreach ($plugins_values as $value) {
            if (!isset($nodes[$value['node_id']]['plugins'])) $nodes[$value['node_id']]['plugins'] = array ();

            $nodes[$value['node_id']]['plugins'][$value['plugin_name']] = array (
                'type' => $value['plugin_type'],
                'name' => $value['plugin_name'],
                'value' => $value[0]->getValue()
            );
        }

        foreach ($nodes as $key => &$node) {
            $path = $this->getPath($key);

            $name_path = array ();

            foreach ($path as $v) {
                if (isset($nodes[$v]['plugins']['fireice_node_name']['value'])) $name_path[] = $nodes[$v]['plugins']['fireice_node_name']['value'];
                else $name_path[] = $key;
            }

            $node['url'] = array (
                'id' => implode('/', $path),
                'name' => implode('/', $name_path)
            );
        }

        $hierarchy = array ();

        foreach ($nodes as $key => $val) {
            $childs = $this->getChilds($key);

            $hierarchy[$key] = count($childs) > 0 ? $childs : null;
        }

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

}
