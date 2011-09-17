<?php

namespace fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity;
                                                             
class right
{                                                                                              
    private $rights = array();     
           
    public function getRights()
    {        
        $ret = array();
        
        foreach ($this->rights as $key=>$val)
        {
            list($right, $module_id) = explode('_', $key);	
            
            if (!isset($ret[$module_id])) 
                $ret[$module_id] = array();
            
            $ret[$module_id][] = $right;
        }	 
        
        return $ret;
    }     
    
    public function __get($name)
    {
        return false;	
    }         
    
    public function __set($name, $value)
    {
        $this->rights[$name] = $value;	
    }     
}