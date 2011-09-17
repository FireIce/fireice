<?php

namespace fireice\FireiceSiteTree\Dialogs\DialogsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use fireice\FireiceSiteTree\Dialogs\DialogsBundle\Model\UsersModel;

class UsersController extends Controller
{                  
    public function getUsersAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');   
        $acl = $this->get('acl');
        
        $users_model = new UsersModel($em, $acl);
        
        $users = $users_model->getUsers();
        
        //print_r($users); exit;
        
        $response = new Response(json_encode($users));
        $response->headers->set('Content-Type', 'application/json');      
                                             
        return $response;             
    }
    
    public function getUserDataAction()
    {   
        $em = $this->get('doctrine.orm.entity_manager');   
        $acl = $this->get('acl');
        
        $users_model = new UsersModel($em, $acl); 
        
        $user_data = $users_model->getUserData($this->get( 'request' )->get( 'id' ));
        
        //print_r($user_data); exit;
        
        $response = new Response(json_encode($user_data));
        $response->headers->set('Content-Type', 'application/json');      
                                             
        return $response;          
    }
    
    public function editUserAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');   
        $acl = $this->get('acl');
        
        $users_model = new UsersModel($em, $acl);         
        
        $users_model->editUser($this->get( 'request' ));
        
        $response = new Response(json_encode('ok'));
        $response->headers->set('Content-Type', 'application/json');      
                                             
        return $response;          
    }
    
    public function addUserAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');   
        $acl = $this->get('acl');
        
        $users_model = new UsersModel($em, $acl);         
        
        $user = $users_model->addUser($this->get( 'request' ));
        
        $this->get('cache')->updateSiteTreeAccessUser($user);
        
        $response = new Response(json_encode('ok'));
        $response->headers->set('Content-Type', 'application/json');      
                                             
        return $response;                
    }
    
    public function deleteUserAction()
    {
        $em = $this->get('doctrine.orm.entity_manager');   
        $acl = $this->get('acl');
        
        $users_model = new UsersModel($em, $acl);         
        
        $users_model->deleteUser($this->get( 'request' )->get('id'));
        
        $this->get('cache')->deleteSiteTreeAccessUser($this->get( 'request' )->get('id'));
        
        $response = new Response(json_encode('ok'));
        $response->headers->set('Content-Type', 'application/json');      
                                             
        return $response;          
    }
}
