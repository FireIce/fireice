<?php

namespace fireice\Backend\Plugins\Clipart\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="plugin_clipart")
 */
class pluginclipart
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
    protected $original_src;
    /**
     * @ORM\Column(type="string", length=300)         
     */
    protected $original_alt;
    /**
     * @ORM\Column(type="integer")        
     */
    protected $original_x;
    /**
     * @ORM\Column(type="integer")        
     */
    protected $original_y;
    /**
     * @ORM\Column(type="string", length=300)         
     */
    protected $big_src;
    /**
     * @ORM\Column(type="string", length=300)         
     */
    protected $big_alt;
    /**
     * @ORM\Column(type="integer")        
     */
    protected $big_x;
    /**
     * @ORM\Column(type="integer")        
     */
    protected $big_y;
    /**
     * @ORM\Column(type="string", length=300)         
     */
    protected $small_src;
    /**
     * @ORM\Column(type="string", length=300)         
     */
    protected $small_alt;
    /**
     * @ORM\Column(type="integer")        
     */
    protected $small_x;
    /**
     * @ORM\Column(type="integer")        
     */
    protected $small_y;
    /**
     * @ORM\Column(type="string", length=10)         
     */
    protected $type_setting;

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

    public function setOriginalSrc($original_src)
    {
        $this->original_src = $original_src;
    }

    public function getOriginalSrc()
    {
        return $this->original_src;
    }

    public function setOriginalAlt($original_alt)
    {
        $this->original_alt = $original_alt;
    }

    public function getOriginalAlt()
    {
        return $this->original_alt;
    }

    public function setOriginalX($original_x)
    {
        $this->original_x = $original_x;
    }

    public function getOriginalX()
    {
        return $this->original_x;
    }

    public function setOriginalY($original_y)
    {
        $this->original_y = $original_y;
    }

    public function getOriginalY()
    {
        return $this->original_y;
    }

    public function setBigSrc($big_src)
    {
        $this->big_src = $big_src;
    }

    public function getBigSrc()
    {
        return $this->big_src;
    }

    public function setBigAlt($big_alt)
    {
        $this->big_alt = $big_alt;
    }

    public function getBigAlt()
    {
        return $this->big_alt;
    }

    public function setBigX($big_x)
    {
        $this->big_x = $big_x;
    }

    public function getBigX()
    {
        return $this->big_x;
    }

    public function setBigY($big_y)
    {
        $this->big_y = $big_y;
    }

    public function getBigY()
    {
        return $this->big_y;
    }

    public function setSmallSrc($small_src)
    {
        $this->small_src = $small_src;
    }

    public function getSmallSrc()
    {
        return $this->small_src;
    }

    public function setSmallAlt($small_alt)
    {
        $this->small_alt = $small_alt;
    }

    public function getSmallAlt()
    {
        return $this->small_alt;
    }

    public function setSmallX($small_x)
    {
        $this->small_x = $small_x;
    }

    public function getSmallX()
    {
        return $this->small_x;
    }

    public function setSmallY($small_y)
    {
        $this->small_y = $small_y;
    }

    public function getSmallY()
    {
        return $this->small_y;
    }

    public function setTypeSetting($type_setting)
    {
        $this->type_setting = $type_setting;
    }

    public function getTypeSetting()
    {
        return $this->type_setting;
    }

    public function setValue($value)
    {
        $this->original_src = $value['original_src'];
        $this->original_alt = $value['original_alt'];
        $this->original_x = $value['original_x'];
        $this->original_y = $value['original_y'];

        $this->big_src = $value['big_src'];
        $this->big_alt = $value['big_alt'];
        $this->big_x = $value['big_x'];
        $this->big_y = $value['big_y'];

        $this->small_src = $value['small_src'];
        $this->small_alt = $value['small_alt'];
        $this->small_x = $value['small_x'];
        $this->small_y = $value['small_y'];

        $this->type_setting = trim($value['type_setting']);
    }

    public function getValue()
    {
        return array (
            'original_src' => $this->original_src,
            'original_alt' => $this->original_alt,
            'original_x' => $this->original_x,
            'original_y' => $this->original_y,
            'big_src' => $this->big_src,
            'big_alt' => $this->big_alt,
            'big_x' => $this->big_x,
            'big_y' => $this->big_y,
            'small_src' => $this->small_src,
            'small_alt' => $this->small_alt,
            'small_x' => $this->small_x,
            'small_y' => $this->small_y,
            'type_setting' => $this->type_setting,
        );
    }

}