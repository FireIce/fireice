<?php

namespace fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

//use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="modules_plugins_link")
 */
class modulespluginslink
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
    protected $up_link;
    /**
     * @ORM\Column(type="integer") 
     */
    protected $up_plugin;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUpLink($up_link)
    {
        $this->up_link = $up_link;
    }

    public function getUpLink()
    {
        return $this->up_link;
    }

    public function setUpPlugin($up_plugin)
    {
        $this->up_plugin = $up_plugin;
    }

    public function getUpPlugin()
    {
        return $this->up_plugin;
    }

}
