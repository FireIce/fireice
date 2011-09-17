<?php

namespace fireice\FireiceSiteTree\TreeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class messages
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
    protected $send_from;
    
    /**
     * @ORM\Column(type="integer") 
     */
    protected $send_for;    
    
    /**   
     * @ORM\Column(type="string", length=255)   
     */
    protected $subject;  
    
    /**
     * @ORM\Column(type="string", length=1000) 
     */
    protected $message;    
    
    /**
     * @ORM\Column(type="datetime")         
     */
    protected $date;  
    
    /**
     * @ORM\Column(type="integer") 
     */
    protected $is_read;      
    
    
    public function __construct()
    {
        $this->date = new \DateTime();
    }       


    public function setId($id)
    {
        $this->id = $id;
    }  
    public function getId()
    {
        return $this->id;
    }    
    
    public function setSendFrom($send_from)
    {
        $this->send_from = $send_from;
    }    
    public function getSendFrom()
    {
        return $this->send_from;    
    }     
    
    public function setSendFor($send_for)
    {
        $this->send_for = $send_for;
    }    
    public function getSendFor()
    {
        return $this->send_for;    
    }     
    
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }    
    public function getSubject()
    {
        return $this->subject;
    }      
    
    public function setMessage($message)
    {
        $this->message = $message;
    }    
    public function getMessage()
    {
        return $this->message;
    }    
    
    public function setDate($date)
    {
        $this->date = $date;
    }  
    public function getDate()
    {
        return $this->date;
    }    
    
    public function setIsRead($is_read)
    {
        $this->is_read = $is_read;
    }  
    public function getIsRead()
    {
        return $is_read;
    }     
}