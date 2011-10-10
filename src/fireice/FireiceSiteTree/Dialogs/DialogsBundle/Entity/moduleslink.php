<?php

namespace fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity;

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

}
