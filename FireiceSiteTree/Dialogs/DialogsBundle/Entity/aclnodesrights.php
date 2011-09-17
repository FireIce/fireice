<?php

namespace fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="acl_nodes_not_rights")
 */
class aclnodesrights
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")      
     */
    protected $id;
    
    /**
     * @ORM\Column(type="integer") 
     */
    protected $up_modules_link;    
    
    /**
     * @ORM\Column(type="integer") 
     */
    protected $up_user;   
    
    /**
     * @ORM\Column(type="integer") 
     */
    protected $not_rights;     
    

    public function setId($id)
    {
        $this->id = $id;
    }  
    public function getId()
    {
        return $this->id;
    }    
    
    public function setUpModulesLink($up_modules_link)
    {
        $this->up_modules_link = $up_modules_link;
    }  
    public function getUpModulesLink()
    {
        return $this->up_modules_link;
    }   
    
    public function setUpUser($up_user)
    {
        $this->up_user = $up_user;
    }  
    public function getUpUser()
    {
        return $this->up_user;
    }     
    
    public function setNotRights($not_rights)
    {
        $this->not_rights = $not_rights;
    }  
    public function getNotRights()
    {
        return $this->not_rights;
    }      
}
