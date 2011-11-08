<?php

namespace fireice\Backend\Dialogs\Entity;

class module
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function __construct($id=null)
    {
        if (null !== $id) $this->setId($id);
    }
}
