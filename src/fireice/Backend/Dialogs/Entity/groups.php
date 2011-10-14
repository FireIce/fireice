<?php

namespace fireice\Backend\Dialogs\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class groups
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")   
     */
    protected $id;
    /**
     * @ORM\Column(type="string", length=45)
     * @Assert\NotBlank
     */
    protected $name;
    /**
     * @ORM\Column(type="string", length=45, nullable="TRUE") 
     */
    protected $title;

    //protected $right;

    public function getConfig()
    {
        return array (
            0 => array ('type' => 'text', 'name' => 'name', 'title' => 'Имя'),
            1 => array ('type' => 'text', 'name' => 'title', 'title' => 'Название'),
        );
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setRight($right)
    {
        $this->right = $right;
    }

    public function getRight()
    {
        return $this->right;
    }

}