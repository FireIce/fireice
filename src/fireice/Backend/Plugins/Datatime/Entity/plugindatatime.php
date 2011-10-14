<?php

namespace fireice\Backend\Plugins\Datatime\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="plugin_datatime")
 */
class plugindatatime
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")    
     * @Assert\Type("numeric")       
     */
    protected $id;
    /**
     * @ORM\Column(type="string", length=45) 
     * @Assert\NotBlank
     */
    protected $data;
    /**
     * @ORM\Column(type="string", length=45)  
     * @Assert\NotBlank    
     */
    protected $time;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setTime($time)
    {
        $this->time = $time;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function setValue($datatime)
    {
        $this->data = $datatime['data'];
        $this->time = $datatime['time'];
    }

    public function getValue()
    {
        return array (
            'data' => $this->data,
            'time' => $this->time
        );
    }

}