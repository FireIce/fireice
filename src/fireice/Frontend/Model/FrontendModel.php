<?php

namespace fireice\Frontend\Model;

use Doctrine\ORM\EntityManager;

class FrontendModel
{
    protected $em;
    protected $acl;
    protected $current_user;
    private $server_is_busy = false;
    protected $sitetree;
    protected $access;
    protected $container;

    public function __construct(EntityManager $em, $acl, $cache, $container)
    {
        $this->em = $em;
        $this->acl = $acl;
        $this->container = $container;

        $this->sitetree = $cache->getSiteTreeStructure();
        $this->access = $cache->getSiteTreeAccess();


        if ($this->sitetree === false || $this->access === false) {
            $this->server_is_busy = true;
        } else {
            $user = $this->acl->current_user;

            if (is_object($user)) {
                $this->current_user = array (
                    'name' => $user->getLogin(),
                    'anon' => false
                );
            } else {
                $this->current_user = array (
                    'name' => 'anonim',
                    'anon' => true
                );
            }
        }
    }

    public function checkServerBusy()
    {
        return $this->server_is_busy;
    }

    public function getNodeInfo($node_id, $language)
    {
        $node = $this->sitetree['nodes'][$node_id];
        $arr = array (
            'id' => $node_id,
            'parent' => $node['up_parent'],
            'name' => isset($node['plugins'][$language]['fireice_node_name']['value']) ? $node['plugins'][$language]['fireice_node_name']['value'] : $node_id,
            'title' => isset($node['plugins'][$language]['fireice_node_title']['value']) ? $node['plugins'][$language]['fireice_node_title']['value'] : '[Узел без названия]',);
        if ('yes' === $this->container->getParameter('multilanguage')) {
            $arr['path'] = $language.'/'.$node['url']['name'];
        } else {
            $arr['path'] = $node['url']['name'];
        }
        return $arr;
    }

    public function getChilds($node_ident)
    {
        $ret = array ();

        foreach ($this->sitetree['nodes'] as $key => $val) {
            if ($val['up_parent'] == $node_ident && $this->checkAccess($key)) $ret[] = $key;
        }

        return $ret;
    }

    public function getMenu($node_id, $language)
    {
        $data = array ();

        $childs = $this->getChilds($node_id);

        foreach ($childs as $node) {
            $data[$node] = $this->getNodeInfo($node, $language);
        }

        return $data;
    }

    public function inChilds($node, $childs, $language)
    {
        $isInt = preg_match("|^[\d]+$|", $node) === 1;

        if (preg_match("|^[\d]+$|", $node) === 1) {
            if (in_array($node, $childs)) return $node;
            else return false;
        }
        else {
            foreach ($childs as $v) {
                $info = $this->getNodeInfo($v, $language);

                if ($node === $info['name']) return $v;
            }

            return false;
        }
    }

    public function getNodeModules($id, $language)
    { 
        return $this->sitetree['nodes'][$id]['sitetree_module'][$language] + $this->sitetree['nodes'][$id]['user_modules'][$language];
    }

    public function getNodeUsersModules($id, $language)
    { 
        return $this->sitetree['nodes'][$id]['user_modules'][$language];
    }

    public function getNavigation($id, $language)
    {
        $node = $this->getNodeInfo($id, $language);

        $return = array ($node);

        if ($id != '1') {
            $return = array_merge($this->getNavigation($node['parent'], $language), $return);
        }

        return $return;
    }

    public function checkAccess($id)
    {
        if ($this->access[$id] === 'true') return true;

        return false;
    }

    public function getUser()
    {
        return $this->current_user;
    }

    public function getMenuHierarchy($navigation)
    {
        if ($navigation !== array () && is_array($navigation)) {

            $first = $navigation[0];
            $next = (isset($navigation[1])) ? $navigation[1] : false;

            $childs = $this->getChilds($first['id']);

            $return = array ();
            foreach ($childs as $val2) {
                $tmp = $this->getNodeInfo($val2);

                if (false !== $next && $next['id'] == $tmp['id']) {

                    $tmp['childs'] = $this->getMenuHierarchy(array_slice($navigation, 1));
                } else {
                    $tmp['childs'] = array ();
                }

                $return[] = $tmp;
            }

            return $return;
        }
    }

    public function getNodeStatus($id)
    {
        return $this->sitetree['nodes'][$id]['status'];
    }

}
