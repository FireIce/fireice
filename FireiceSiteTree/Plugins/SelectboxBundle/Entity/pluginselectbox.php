<?php

namespace fireice\FireiceSiteTree\Plugins\SelectboxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
                                                
/**
 * @ORM\Entity
 * @ORM\Table(name="plugin_selectbox")
 */                                                                    
class pluginselectbox
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
    protected $value;      
     
    
    public function setId($id)
    {
        $this->id = $id;
    }  
    public function getId()
    {
        return $this->id;
    }      
    
    public function setValue($value)
    {
        $this->value = $value;
    }  
    public function getValue()
    {
        return $this->value;
    }     
        
           
 
}