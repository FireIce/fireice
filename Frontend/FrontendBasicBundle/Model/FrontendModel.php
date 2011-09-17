<?php

namespace fireice\Frontend\FrontendBasicBundle\Model;

use Doctrine\ORM\EntityManager;

class FrontendModel
{        
    protected $em;   
    protected $acl;
    
    protected $current_user; 
    private $server_is_busy = false;
             
    protected $sitetree; 
    protected $access;

    public function __construct(EntityManager $em, $acl, $cache)
    {
        $this->em = $em; 
        $this->acl = $acl;

        ////////////////////////////////////////////////////////////////////////////////////////////

        $this->sitetree = $cache->getSiteTreeStructure();
        $this->access = $cache->getSiteTreeAccess();

        if ($this->sitetree === false || $this->access === false)
        {
            $this->server_is_busy = true;
        }
        else
        {
            $user = $this->acl->current_user;
        
            if (is_object($user))
            {
                $this->current_user = array(
                    'name' => $user->getLogin(),
                    'anon' => false
                );
            }
            else
            {
                $this->current_user = array(
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
    
    public function getNodeInfo($node_id)
    { 
        $node = $this->sitetree['nodes'][$node_id];          
       
        return array(
            'parent'  => $node['up_parent'],
            'name'    => isset($node['plugins']['fireice_node_name']['value'])?$node['plugins']['fireice_node_name']['value']:$node_id,
            'title'   => isset($node['plugins']['fireice_node_title']['value'])?$node['plugins']['fireice_node_title']['value']:'[Узел без названия]',
            'path'    => $node['url']['name']            
        );        
    }        
    
    public function getChilds($node_ident)
    {
        $ret = array();
        
        foreach ($this->sitetree['nodes'] as $key=>$val)
        {
            if ($val['up_parent'] == $node_ident && $this->checkAccess($key)) 
                $ret[] = $key;
        }        
        
        return $ret;
    }
    
    public function getMenu($node_id)
    {
        $data = array();
        
        $childs = $this->getChilds($node_id);
        
        foreach ($childs as $node)                      
        {
            $data[$node] = $this->getNodeInfo($node);                  
        }

        return $data;
    }    
    
    public function inChilds($node, $childs)
    {      
        $is_int = preg_match("|^[\d]+$|", $node) === 1;
        
        if (preg_match("|^[\d]+$|", $node) === 1)
        {
            if (in_array($node, $childs))
                return $node;
            else
                return false;             
        }
        else
        {
            foreach ($childs as $v)
            {
                $info = $this->getNodeInfo($v);
               
                if ($node === $info['name'])
                    return $v;                    
            }
            
            return false;
        }
    }
            
    public function getNodeModules($id)
    {
        return $this->sitetree['nodes'][$id]['sitetree_module'] + $this->sitetree['nodes'][$id]['user_modules'];
    }
    
    public function getNodeUsersModules($id)
    {
        return $this->sitetree['nodes'][$id]['user_modules'];
    }    
    
    public function getNavigation($id)
    {                
        $node = $this->getNodeInfo( $id );

        $return = array($node);

        if ($id != '1')
        {                                    
            $return = array_merge($this->getNavigation($node['parent']), $return); 
        }
        
        return $return;         
    }           
    
    public function checkAccess($id)
    {
        if ($this->access[$id] === 'true')
            return true;
        
        return false;        
    }
    
    public function getUser()
    {
        return $this->current_user;
    }
}
                                                           
