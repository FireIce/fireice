<?php

namespace fireice\FireiceSiteTree\Plugins\CheckboxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert; 
                                                
/**
 * @ORM\Entity
 * @ORM\Table(name="plugin_checkbox")
 */                                                                    
class plugincheckbox
{                                                                                          
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")    
     * @Assert\Type("numeric")                                                  
     */
    protected $id;
    
    /**
     * @ORM\Column(type="integer")        
     */
    protected $id_group;  
    
    /**
     * @ORM\Column(type="string", length=45)    
     */
    protected $id_data;      
           
    /**
     * @ORM\Column(type="integer")        
     */
    protected $value;      
     
    
    public function setId($id)
    {
        $this->id = $id;
    }  
    public function getId()
    {
        return $this->id;
    }   
    
    public function setIdGroup($id_group)
    {
        $this->id_group = $id_group;
    }  
    public function getIdGroup()
    {
        return $this->id_group;
    }    
    
    public function setIdData($id_data)
    {
        $this->id_data = $id_data;
    }  
    public function getIdData()
    {
        return $this->id_data;
    }     
    
    public function setValue($value)
    {
        $this->value = $value;
    }  
    public function getValue()
    {
        return $this->value;
    }     
        
    public function setCheckbox($data)
    {
        
    }
    
    public function getCheckbox()
    {

        
    }    
 
}