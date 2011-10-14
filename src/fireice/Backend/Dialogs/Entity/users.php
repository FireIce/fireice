<?php

namespace fireice\Backend\Dialogs\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class users implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")                                                
     */
    protected $id;
    /**
     * @ORM\Column(type="string", length=45)      
     */
    protected $login;
    /**
     * @ORM\Column(type="string", length=45)        
     */
    protected $password;
    /**
     * @ORM\Column(type="string", length=45)     
     */
    protected $type;
    /**
     * @ORM\Column(type="string", length=45)       
     */
    protected $fname;
    /**
     * @ORM\Column(type="string", length=45)      
     */
    protected $sname;
    /**
     * @ORM\Column(type="string", length=45)  
     */
    protected $email;
    /**
     * @ORM\Column(type="integer")
     */
    protected $groups;
    protected $right;
    protected $roles = array ('ROLE_USER');

    public function getConfig()
    {
        return array (
            0 => array ('type' => 'text', 'name' => 'login', 'title' => 'Логин'),
            1 => array ('type' => 'text', 'name' => 'password', 'title' => 'Пароль'),
            2 => array ('type' => 'text', 'name' => 'type', 'title' => 'Тип'),
            3 => array ('type' => 'text', 'name' => 'fname', 'title' => 'Имя'),
            4 => array ('type' => 'text', 'name' => 'sname', 'title' => 'Фамилия'),
            5 => array ('type' => 'text', 'name' => 'email', 'title' => 'Е-мейл'),
            6 => array ('type' => 'selectbox', 'name' => 'groups', 'title' => 'Группа'),
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

    public function setLogin($login)
    {
        $this->login = $login;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setFname($fname)
    {
        $this->fname = $fname;
    }

    public function getFname()
    {
        return $this->fname;
    }

    public function setSname($sname)
    {
        $this->sname = $sname;
    }

    public function getSname()
    {
        return $this->sname;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function setRight($right)
    {
        $this->right = $right;
    }

    public function getRight()
    {
        return $this->right;
    }

    public function __toString()
    {
        return $this->login;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername()
    {
        return $this->login;
    }

    public function eraseCredentials()
    {
        
    }

    public function isGranted()
    {
        
    }

    public function equals(UserInterface $account)
    {
        if (!$account instanceof $this) {
            return false;
        }

        if ($this->password !== $account->getPassword()) {
            return false;
        }

        if ($this->getSalt() !== $account->getSalt()) {
            return false;
        }

        if ($this->login !== $account->getUsername()) {
            return false;
        }
        return true;
    }

}