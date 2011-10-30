<?php

namespace fireice\Backend\Plugins\Uploadimage\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="plugin_uploadimage")
 */
class pluginuploadimage
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
     * @ORM\Column(type="integer")        
     */
    protected $id_data;
    /**
     * @ORM\Column(type="string", length=300)         
     */
    protected $alt;
    /**
     * @ORM\Column(type="string", length=300)         
     */
    protected $src;

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

    public function setAlt($alt)
    {
        $this->alt = $alt;
    }

    public function getAlt()
    {
        return $this->alt;
    }

    public function setSrc($src)
    {
        $this->src = $src;
    }

    public function getSrc()
    {
        return $this->src;
    }

    public function setValue($value)
    {
        $this->alt = $value['alt'];
        $this->src = $value['src'];
    }

    public function getValue()
    {
        return array (
            'alt' => $this->alt,
            'src' => $this->src
        );
    }

}