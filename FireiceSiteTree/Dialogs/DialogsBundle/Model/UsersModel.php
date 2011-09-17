<?php 

namespace fireice\FireiceSiteTree\Dialogs\DialogsBundle\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

use fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\users;
use fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\UserGroups;

class UsersModel
{
    protected $em;
    protected $acl;

    public function __construct(EntityManager $em, $acl)
    {
        $this->em = $em;  
        $this->acl = $acl;
    }                                                         
    
    public function findAll()
    {
        $users = $this->em->getRepository('DialogsBundle:users')->findAll();
        return $users;
    }       
    
    public function getUsers()
    {
    	$query = $this->em->createQuery('SELECT us, gr FROM DialogsBundle:users us, DialogsBundle:groups gr 
    	                                 WHERE us.groups=gr.id
                                         ORDER BY us.id');
		 
		$result = $query->getScalarResult();        
        
        return $result;
    }                                                            
    
	public function getPlugins()
	{
		$this->plugins = array( );		
        
        $module = 'fireice\\FireiceSiteTree\\Dialogs\\DialogsBundle\\Entity\\users';
        $module = new $module();   
        
        $config = $module->getConfig();
        
        return true === empty( $config ) ? null : $module->getConfig();        
	}     
    
    public function getUserData($user_id)
    {
    	$query = $this->em->createQuery("SELECT user FROM DialogsBundle:users user 
    	                                 WHERE user.id='".$user_id."'");
        
        $query->setMaxResults(1);
		 
		$result = $query->getArrayResult();   
        
        $data = array();        
        
        $module = 'fireice\\FireiceSiteTree\\Dialogs\\DialogsBundle\\Entity\\users';
        $module = new $module();
        
        foreach ($module->getConfig() as $plugin)
        {
            if (count($result) > 0)
             {
                if ($plugin['name'] != 'groups')
                    $data[$plugin['name']] = $plugin + array('value' => $result[0][$plugin['name']]);    
                else
                    $data[$plugin['name']] = $plugin + array('value' => $this->getGroups( $result[0]['groups']));
            }
            else
            {
                if ($plugin['name'] != 'groups')
                   $data[$plugin['name']] = $plugin + array('value' => '');    
                else
                   $data[$plugin['name']] = $plugin + array('value' => $this->getGroups());                   
            }            
        }                
        
        return $data;
    }
    
    public function getGroups($current = null)
    {
        $groups = $this->em->getRepository('DialogsBundle:groups')->findAll();
        
        $ret = array();
        
        foreach ($groups as $val)
        {                
            $checked = ($current === $val->getId()) ? 1 : 0;
            
            $ret[$val->getId()] = array(
                'checked' => $checked,
                'value'   => $val->getTitle()
            );
        }
                
        return $ret;     	
    }  
    
    public function editUser($request)
    {
		if( null !== $m = $this->getPlugins() )
		{												
			$user = $this->em->getRepository('DialogsBundle:users')->findOneBy(array('id' => $request->get('id')));

		    foreach( $m as $o )
		    {    
			    $set_method = 'set'.ucfirst($o['name']);		    		
                            
                $user->$set_method( $request->get($o['name']) );
		    } 	

		    $this->em->persist($user);
            $this->em->flush();                                
		}                
    }
    
    public function addUser($request)
    {        
        if( null !== $m = $this->getPlugins() )
		{												
			$user = new users();

		    foreach( $m as $o )
		    {    
			    $set_method = 'set'.ucfirst($o['name']);		    		
                            
                $user->$set_method( $request->get($o['name']) );
		    } 	

		    $this->em->persist($user);
            $this->em->flush();   
            
            return $user;
		}                  
    }
    
    public function deleteUser($id_user)
    {
        $user = $this->em->getRepository('DialogsBundle:users')->findOneBy(array('id' => $id_user));    
        
		$this->em->remove($user);
        $this->em->flush();         
    }
    
}
                                                           