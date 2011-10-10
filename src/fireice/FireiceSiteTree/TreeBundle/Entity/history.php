<?php

namespace fireice\FireiceSiteTree\TreeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class history
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
    protected $up_user;
    /**
     * @ORM\Column(type="integer")          
     */
    protected $up;
    /**
     * @ORM\Column(type="string", length=45)        
     */
    protected $up_type_code;
    /**
     * @ORM\Column(type="string", length=45)         
     */
    protected $action_code;
    /**
     * @ORM\Column(type="datetime")         
     */
    protected $date_create;

    public function __construct()
    {
        $this->date_create = new \DateTime();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUpUser($up_user)
    {
        $this->up_user = $up_user;
    }

    public function getUpUser()
    {
        return $this->up_user;
    }

    public function setUp($up)
    {
        $this->up = $up;
    }

    public function getUp()
    {
        return $this->up;
    }

    public function setUpTypeCode($up_type_code)
    {
        $this->up_type_code = $up_type_code;
    }

    public function getUpTypeCode()
    {
        return $this->up_type_code;
    }

    public function setActionCode($action_code)
    {
        $this->action_code = $action_code;
    }

    public function getActionCode()
    {
        return $this->action_code;
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