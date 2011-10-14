<?php

namespace fireice\Backend\Dialogs\Entity;

use Doctrine\ORM\Mapping as ORM;

//use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class modules
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
    protected $idd;
    /**
     * @ORM\Column(type="integer") 
     */
    protected $cid;
    /**
     * @ORM\Column(type="integer", nullable="TRUE") 
     */
    protected $eid;
    /**
     * @ORM\Column(type="string", length=1)   
     */
    protected $final;
    /**
     * @ORM\Column(type="string", length=45)   
     */
    protected $type;
    /**
     * @ORM\Column(type="string", length=45)   
     */
    protected $table_name;
    /**
     * @ORM\Column(type="string", length=45)   
     */
    protected $name;
    /**
     * @ORM\Column(type="string", length=45)   
     */
    protected $status;
    /**
     * @ORM\Column(type="datetime")         
     */
    protected $date_create;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setIdd($idd)
    {
        $this->idd = $idd;
    }

    public function getIdd()
    {
        return $this->idd;
    }

    public function setCid($cid)
    {
        $this->cid = $cid;
    }

    public function getCid()
    {
        return $this->cid;
    }

    public function setEid($eid)
    {
        $this->eid = $eid;
    }

    public function getEid()
    {
        return $this->eid;
    }

    public function setFinal($final)
    {
        $this->final = $final;
    }

    public function getFinal()
    {
        return $this->final;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setTableName($table_name)
    {
        $this->table_name = $table_name;
    }

    public function getTableName()
    {
        return $this->table_name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setDateCreate($date_create)
    {
        $this->date_create = $date_create;
    }

    public function getDateCreate()
    {
        return $this->date_create;
    }

}
