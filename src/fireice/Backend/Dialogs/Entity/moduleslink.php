<?php

namespace fireice\Backend\Dialogs\Entity;

use Doctrine\ORM\Mapping as ORM;

//use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="modules_link")
 */
class moduleslink
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
    protected $up_tree;
    /**
     * @ORM\Column(type="integer") 
     */
    protected $up_module;
    /**
     * @ORM\Column(type="string", length=5) 
     */
    protected $language;
    /**
     * @ORM\Column(type="is_main", type="integer", nullable="TRUE") 
     */
    protected $is_main;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUpTree($up_tree)
    {
        $this->up_tree = $up_tree;
    }

    public function getUpTree()
    {
        return $this->up_tree;
    }

    public function setUpModule($up_module)
    {
        $this->up_module = $up_module;
    }

    public function getUpModule()
    {
        return $this->up_module;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setIsmain($is_main)
    {
        $this->is_main = $is_main;
    }

    public function getIsmain()
    {
        return $this->is_main;
    }

}
