<?php

namespace fireice\FireiceSiteTree\Modules\BasicBundle\Model;

//use Doctrine\ORM\EntityManager;
use example\Modules\ModuleContactsBundle\Entity\history;  

class BackendModel extends GeneralModel
{    
    public function getBackendData( $sitetree_id, $acl, $module_id )
    {
        $values = array();
        
        foreach ($this->getPlugins() as $plugin)
        {   
            if (!isset($values[$plugin->getValue('type')]))       
            {                
                $values[$plugin->getValue('type')] = $plugin->getBackendModuleData($sitetree_id, $this->bundle_name.':'.$this->entity_name, $module_id, self::TYPE_ITEM);
            }            
        }
        
        $data = array(); 
        
        //print_r($values); exit;
        
        foreach ($this->getPlugins() as $plugin)
        {
            $type = $plugin->getValue('type');
            
            if (count($values[$type]) > 0)
            {
                foreach ($values[$type] as $val)
                {
                    if ($val['plugin_name'] == $plugin->getValue('name'))
                    {
                        $data[$plugin->getValue('name')] = $plugin->getValues() + array('value' => $val['plugin_value']);
                        break;
                    }
                }
                
                if (!isset($data[$plugin->getValue('name')]))
                    $data[$plugin->getValue('name')] = $plugin->getNull();
                
            } else { $data[$plugin->getValue('name')] = $plugin->getNull(); }            
        }        
       
        if (strpos($this->bundle_name, 'Fireice') === 0)
        {
            return array(
                'type' => 'item',
                'data' => $data
            ); 
        }
                           
        $module = $this->em->getRepository('DialogsBundle:modules')->findOneBy(array('name' => $this->bundle_name, 'final' => 'Y'));
            
        $service_module = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\module();	
        $service_module->setId($module->getId());      
            
        $proveeditor           = ($acl->checkUserPermissions($sitetree_id, $service_module, false, $acl->getValueMask('proveeditor'))) ? 'show' : 'hide';
        $provemaineditor       = ($acl->checkUserPermissions($sitetree_id, $service_module, false, $acl->getValueMask('provemaineditor'))) ? 'show' : 'hide';   
        $sendtoproveeditor     = ($acl->checkUserPermissions($sitetree_id, $service_module, false, $acl->getValueMask('sendtoproveeditor'))) ? 'show' : 'hide';        
        $sendtoprovemaineditor = ($acl->checkUserPermissions($sitetree_id, $service_module, false, $acl->getValueMask('sendtoprovemaineditor'))) ? 'show' : 'hide';
        $returnwriter          = ($acl->checkUserPermissions($sitetree_id, $service_module, false, $acl->getValueMask('returnwriter'))) ? 'show' : 'hide';   
        $returneditor          = ($acl->checkUserPermissions($sitetree_id, $service_module, false, $acl->getValueMask('returneditor'))) ? 'show' : 'hide';        
                                                                     
        // Определение статуса
        $status = null;

        foreach ($values as $val)
        {             
             if (isset($val[0]['status']))
                 $status = $val[0]['status'];
             else
                 $status = 'active';
             
             break;
        }        
        
        // Если обычный журналист
        if ($proveeditor == 'hide' && $provemaineditor == 'hide')
        {
            if ($status == 'sendtoproveeditor' || $status == 'sendtoprovemaineditor')
                $data = 'send_to_prove';
        }
                                                        
        // Если редактор
        if ($proveeditor == 'show' || $returnwriter == 'show')
        {                    
            if ($status != 'sendtoproveeditor')
            {
                $proveeditor = 'hide';
                $returnwriter = 'hide';
            }
            
            if ($status == 'sendtoprovemaineditor')
                $data = 'send_to_prove';            
        }     
        
        // Если главный редактор
        if ($provemaineditor == 'show' || $returneditor == 'show')
        {                    
            if ($status != 'sendtoprovemaineditor')
            {
                $provemaineditor = 'hide';
                $returneditor = 'hide';
            }
        }          
 //print_r($data); exit;
        return array(
            'type'        => 'item',
            'data'        => $data,
            
            'proveeditor'           => $proveeditor,
            'provemaineditor'       => $provemaineditor,
            'sendtoproveeditor'     => $sendtoproveeditor,
            'sendtoprovemaineditor' => $sendtoprovemaineditor,
            'returnwriter'          => $returnwriter,
            'returneditor'          => $returneditor            
        );                  
    }        

	public function createEdit( $request, $security, $acl )
	{		                                      
        if (strpos($this->bundle_name, 'Fireice') === 0)
        {
            // Если это модуль узла дерева, то обычные действия    
            foreach ($this->getPlugins() as $plugin)
            {                        
                $query = $this->em->createQuery("
                    SELECT 
                        md.idd
                    FROM 
                        ".$this->bundle_name.':'.$this->entity_name." md,
                        DialogsBundle:moduleslink m_l,
                        DialogsBundle:modulespluginslink mp_l
                    WHERE md.status = 'active'            
                    AND m_l.up_tree = ".$request->get('id')."
                    AND m_l.up_module = ".$request->get('module_type')."
                    AND m_l.id = mp_l.up_link
                    AND mp_l.up_plugin = md.idd
                    AND md.final = 'Y'
                    AND md.plugin_name = '".$plugin->getValue('name')."'
                    AND md.plugin_type = '".$plugin->getValue('type')."'");
        
                $result = $query->getResult();
            
                if (count($result) > 0)
                {
                    $result = $result[0];
                      
                    $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));
        
                    $history = new history(); 
                    $history->setUpUser($security->getToken()->getUser()->getId());
                    $history->setUp($result['idd']);
	                $history->setUpTypeCode($this->entity_name);
                    $history->setActionCode('edit_record'); 
		            $this->em->persist($history);
                    $this->em->flush();    
                
                    $hid = $history->getId();  
                
    	            $query = $this->em->createQuery("UPDATE ".$this->bundle_name.':'.$this->entity_name." md SET md.final='N', md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND md.final = 'Y' AND md.eid IS NULL");		 
		            $query->getResult();                  
                
                    $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                    $new_module_record = new $new_module_record();
                    $new_module_record->setIdd($result['idd']);
                    $new_module_record->setCid($hid);
                    $new_module_record->setFinal('Y');
                    $new_module_record->setPluginId($plugin_id);
                    $new_module_record->setPluginType($plugin->getValue('type'));
                    $new_module_record->setPluginName($plugin->getValue('name'));
                    $new_module_record->setStatus('active');
		            $this->em->persist($new_module_record);
                    $this->em->flush();                          
                }
                else
                {
                    $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));    
                
                    $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                    $new_module_record = new $new_module_record();
                    $new_module_record->setFinal('T');
                    $new_module_record->setPluginId($plugin_id);
                    $new_module_record->setPluginType($plugin->getValue('type'));
                    $new_module_record->setPluginName($plugin->getValue('name'));
                    $new_module_record->setStatus('inserting');
		            $this->em->persist($new_module_record);
                    $this->em->flush();    
                
                    $history = new history(); 
                    $history->setUpUser($security->getToken()->getUser()->getId());
                    $history->setUp($new_module_record->getId());
	                $history->setUpTypeCode($this->entity_name);
                    $history->setActionCode('add_record'); 
		            $this->em->persist($history);
                    $this->em->flush();                
                
                    $new_module_record->setIdd($new_module_record->getId());
                    $new_module_record->setCid($history->getId());    
                    $new_module_record->setFinal('Y');
                    $new_module_record->setStatus('active');  
		            $this->em->persist($new_module_record);
                    $this->em->flush();   
 
                    $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array(
                        'up_tree'   => $request->get('id'),
                        'up_module' => $request->get('module_type')
                    ));
                
                    $module_plugin_link = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\modulespluginslink();
                    $module_plugin_link->setUpLink($modulelink->getId());
                    $module_plugin_link->setUpPlugin($new_module_record->getId());
		            $this->em->persist($module_plugin_link);
                    $this->em->flush();                  
                }
            }                                                                  
        }
        else
        {        
            // Если пользовательский модуль
            $module = $this->em->getRepository('DialogsBundle:modules')->findOneBy(array('name' => $this->bundle_name));
            
            $service_module = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\module();	
            $service_module->setId($module->getId());      
            
            // Смотрим есть ли у пользователя право утверждать статьи на уровне главного редактора        
            if ($acl->checkUserPermissions($request->get('id'), $service_module, false, $acl->getValueMask('provemaineditor')))
            {
                // Если есть    
                foreach ($this->getPlugins() as $plugin)
                {                        
                    $query = $this->em->createQuery("
                        SELECT 
                            md.idd
                        FROM 
                            ".$this->bundle_name.':'.$this->entity_name." md,
                            DialogsBundle:moduleslink m_l,
                            DialogsBundle:modulespluginslink mp_l
                        WHERE md.eid IS NULL            
                        AND m_l.up_tree = ".$request->get('id')."
                        AND m_l.up_module = ".$request->get('module_type')."
                        AND m_l.id = mp_l.up_link
                        AND mp_l.up_plugin = md.idd
                        AND md.final != 'N'
                        AND md.plugin_name = '".$plugin->getValue('name')."'
                        AND md.plugin_type = '".$plugin->getValue('type')."'");
        
                    $result = $query->getResult();
            
                    if (count($result) > 0)
                    {
                        $result = $result[0];
                      
                        $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));
        
                        $history = new history(); 
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($result['idd']);
	                    $history->setUpTypeCode($this->entity_name);
                        $history->setActionCode('edit_record'); 
		                $this->em->persist($history);
                        $this->em->flush();    
                
                        $hid = $history->getId();  
                    
    	                $query = $this->em->createQuery("UPDATE ".$this->bundle_name.':'.$this->entity_name." md SET md.final='N', md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND md.final != 'N'");		 
		                $query->getResult();                  
                
                        $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                        $new_module_record = new $new_module_record();
                        $new_module_record->setIdd($result['idd']);
                        $new_module_record->setCid($hid);
                        $new_module_record->setFinal('Y');
                        $new_module_record->setPluginId($plugin_id);
                        $new_module_record->setPluginType($plugin->getValue('type'));
                        $new_module_record->setPluginName($plugin->getValue('name'));
                        $new_module_record->setStatus('active');
		                $this->em->persist($new_module_record);
                        $this->em->flush();                          
                    }
                    else
                    {
                        $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));    
                
                        $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                        $new_module_record = new $new_module_record();
                        $new_module_record->setFinal('T');
                        $new_module_record->setPluginId($plugin_id);
                        $new_module_record->setPluginType($plugin->getValue('type'));
                        $new_module_record->setPluginName($plugin->getValue('name'));
                        $new_module_record->setStatus('inserting');
		                $this->em->persist($new_module_record);
                        $this->em->flush();    
                
                        $history = new history(); 
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($new_module_record->getId());
	                    $history->setUpTypeCode($this->entity_name);
                        $history->setActionCode('add_record'); 
		                $this->em->persist($history);
                        $this->em->flush();                
                
                        $new_module_record->setIdd($new_module_record->getId());
                        $new_module_record->setCid($history->getId());    
                        $new_module_record->setFinal('Y');
                        $new_module_record->setStatus('active');  
		                $this->em->persist($new_module_record);
                        $this->em->flush();   
 
                        $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array(
                            'up_tree'   => $request->get('id'),
                            'up_module' => $request->get('module_type')
                        ));
                
                        $module_plugin_link = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\modulespluginslink();
                        $module_plugin_link->setUpLink($modulelink->getId());
                        $module_plugin_link->setUpPlugin($new_module_record->getId());
		                $this->em->persist($module_plugin_link);
                        $this->em->flush();                  
                    }
                }                                                                             
            }
            else
            {
                // Если нет
                foreach ($this->getPlugins() as $plugin)
                {
                    $query = $this->em->createQuery("
                        SELECT 
                            md.idd,
                            md.final 
                        FROM 
                            ".$this->bundle_name.':'.$this->entity_name." md,
                            DialogsBundle:moduleslink m_l,
                            DialogsBundle:modulespluginslink mp_l
                        WHERE (md.final = 'Y' OR md.final = 'W')
                        AND md.eid IS NULL
                        AND m_l.up_tree = ".$request->get('id')."
                        AND m_l.up_module = ".$request->get('module_type')."
                        AND m_l.id = mp_l.up_link
                        AND mp_l.up_plugin = md.idd
                        AND md.plugin_name = '".$plugin->getValue('name')."'
                        AND md.plugin_type = '".$plugin->getValue('type')."'");
        
                    $result = $query->getResult();
            
                    if (count($result) > 0)
                    {
                        $result = $result[0];
                      
                        $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));
        
                        $history = new history(); 
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($result['idd']);
	                    $history->setUpTypeCode($this->entity_name);
                        $history->setActionCode('edit_record'); 
		                $this->em->persist($history);
                        $this->em->flush();    
            
                        $hid = $history->getId();  
                
    	                if ($result['final'] == 'W')
                            $query = $this->em->createQuery("UPDATE ".$this->bundle_name.':'.$this->entity_name." md SET md.final='N', md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND (md.final = 'Y' OR md.final = 'W') AND md.eid IS NULL");		 
		                elseif ($result['final'] == 'Y')
                            $query = $this->em->createQuery("UPDATE ".$this->bundle_name.':'.$this->entity_name." md SET md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND (md.final = 'Y' OR md.final = 'W') AND md.eid IS NULL");
                        $query->getResult();
                
                        $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                        $new_module_record = new $new_module_record();
                        $new_module_record->setIdd($result['idd']);
                        $new_module_record->setCid($hid);
                        $new_module_record->setFinal('W');
                        $new_module_record->setPluginId($plugin_id);
                        $new_module_record->setPluginType($plugin->getValue('type'));
                        $new_module_record->setPluginName($plugin->getValue('name'));
                        $new_module_record->setStatus('edit');
		                $this->em->persist($new_module_record);
                        $this->em->flush();                          
                    }
                    else
                    {
                        $plugin_id = $plugin->setDataInDb($request->get($plugin->getValue('name')));    
                
                        $new_module_record = '\\example\\Modules\\'.$this->bundle_name.'\\Entity\\'.$this->entity_name;
                        $new_module_record = new $new_module_record();
                        $new_module_record->setFinal('T');
                        $new_module_record->setPluginId($plugin_id);
                        $new_module_record->setPluginType($plugin->getValue('type'));
                        $new_module_record->setPluginName($plugin->getValue('name'));
                        $new_module_record->setStatus('inserting');
		                $this->em->persist($new_module_record);
                        $this->em->flush();    
                
                        $history = new history(); 
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($new_module_record->getId());
    	                $history->setUpTypeCode($this->entity_name);
                        $history->setActionCode('edit_record'); 
    		            $this->em->persist($history);
                        $this->em->flush();                
                
                        $new_module_record->setIdd($new_module_record->getId());
                        $new_module_record->setCid($history->getId());    
                        $new_module_record->setFinal('W');
                        $new_module_record->setStatus('edit');  
    		            $this->em->persist($new_module_record);
                        $this->em->flush();   
     
                        $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array(
                            'up_tree'   => $request->get('id'),
                            'up_module' => $request->get('module_type')
                        ));
                
                        $module_plugin_link = new \fireice\FireiceSiteTree\Dialogs\DialogsBundle\Entity\modulespluginslink();
                        $module_plugin_link->setUpLink($modulelink->getId());
                        $module_plugin_link->setUpPlugin($new_module_record->getId());
		                $this->em->persist($module_plugin_link);
                        $this->em->flush();                  
                    }                
                }            
            } 
        }
	}      
    
    public function getHistory($request)
    {
        // +++ Главный запрос, получающий список записей в таблице модуля
        $query = $this->em->createQuery("
            SELECT 
                md.id,
                md.plugin_id,
                md.plugin_type,
                md.cid
            FROM 
                ".$this->bundle_name.':'.$this->entity_name." md,
                DialogsBundle:moduleslink m_l,
                DialogsBundle:modulespluginslink mp_l
            WHERE md.status = 'active' 
            AND m_l.up_tree = ".$request->get('id')."
            AND m_l.up_module = ".$request->get('module_type')."
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd");
            
        $result = $query->getResult();  
        // --- Главный запрос, получающий список записей в таблице модуля
        
        // +++ Обрабатываем результат и забиваем в массивы
        $tmp = array();         // Ид плагинов по группам (группы - типы плагинов)
        $tmp_cid = array();     // Параметр cid по группам
        $cids = array();        // Общий массив параметров cid   
        
        foreach ($result as $val)
        {
            if (!isset($tmp[$val['plugin_type']]))    
                $tmp[$val['plugin_type']] = array();            
            $tmp[$val['plugin_type']][] = $val['plugin_id'];
            
            if (!isset($tmp_cid[$val['plugin_type']]))    
                $tmp_cid[$val['plugin_type']] = array();            
            $tmp_cid[$val['plugin_type']][] = $val['cid'];    
            
            $cids[] = $val['cid'];
        }
        // --- Обрабатываем результат и забиваем в массивы
        
        // +++ Получаем значения плагинов по группам
        $values = array();
        
        foreach ($tmp as $key=>$val)
        {
            $query = $this->em->createQuery("
                SELECT 
                    plg
                FROM 
                    FireicePlugins".ucfirst($key)."Bundle:plugin".$key." plg
                WHERE plg.id IN (".implode(',', $val).")");            
            
            $values[$key] = $query->getResult();
        }    
        // --- Получаем значения плагинов по группам      
        
        // +++ Получаем массив вида [cid] => array(пользователь, действие, дата)
        $query = $this->em->createQuery("
            SELECT 
                ht.id,
                ht.up_user,
                ht.action_code,
                ht.date_create
            FROM 
                ModuleContactsBundle:history ht
            WHERE ht.id IN (".implode(',', $cids).")"); 
        
        $result_cides = $query->getResult();         
        
        $cids = array();        
        $users = array();
        
        foreach ($result_cides as $val)
        {
            $cids[$val['id']] = $val;
            
            if (!in_array($val['up_user'], $users))
                $users[] = $val['up_user'];
        }               
        // --- Получаем массив вида [cid] => array(пользователь, действие, дата)    
               
        // +++ Получаем массив соответсвия ид юзера - имя юзера
        $query = $this->em->createQuery("
            SELECT 
                us.id,
                us.login
            FROM 
                DialogsBundle:users us
            WHERE us.id IN (".implode(',', $users).")");         
        
        $result_users = $query->getResult();
        
        $users = array();
        
        foreach ($result_users as $val)
        {
            $users[ $val['id'] ] = $val['login'];   
        }
        // --- Получаем массив соответсвия ид юзера - имя юзера
         
        // Обходим массив values (значения плагинов по группам) и cid (параметр цид по группам) и формируем 
        // историю выдёргивая инфу записи из соответствующего значения cid и записи в массиве $cids
        $plugins = $this->getPlugins();
        
        $history = array();
        
        for ($i=1; $i<=intval(count($result) / count($plugins)); $i++)
        {
            $history_values = array();
            
            foreach ($plugins as $plugin)
            {
                $value = array_pop($values[$plugin->getValue('type')]);
                $cid = array_pop($tmp_cid[$plugin->getValue('type')]);               

                $plugin_method = 'get'.ucfirst($plugin->getValue('type'));
                
                $history_values[] = array(
                    'title' => $plugin->getValue('title'),
                    'value' => $value->$plugin_method(),
                );                 
            }
            
            if (isset($cids[$cid]))
            {
                // Для получение инфы по записи (всем плагинам записи) берём cid одной из записи
                $data        = $cids[$cid]['date_create'];
                $user        = $users[ $cids[$cid]['up_user'] ];
                $action_code = $cids[$cid]['action_code'];                
            }
            else
            {
                $data        = 'Нет записи в таблице истории!';
                $user        = 'Нет записи в таблице истории!';
                $action_code = 'Нет записи в таблице истории!';                
            }
                                     
            $history[] = array(
                'data'        => $data,
                'user'        => $user,
                'action_code' => $action_code,
                'values'      => $history_values, 
            );             
        }

        //print_r($history); exit;        

        return $history;
    }    
    
    // Была ли страница модуля привязанного к узлу отправлена на подтверждение
    public function checkSendProve($sitetree_id)
    {   /*
        $module = $this->em->getRepository('RightsBundle:modules')->findOneBy(array('name' => $this->bundle_name));
        
        $action = $this->em->getRepository('ModuleContactsBundle:historyactions')->findOneBy(array('modules_id' => $module->getId(), 'name' => 'sendtoprove'));

    	$query = $this->em->createQuery('SELECT ht, mt FROM ModuleContactsBundle:history ht, '.$this->bundle_name.':'.$this->entity_name.' mt 
    	                                 WHERE ht.id_module='.$module->getId().' 
                                         AND ht.id_action='.$action->getId().'
    	                                 AND ht.id_module_content=mt.id
    	                                 AND mt.id_group='.$sitetree_id.' 
    	                                 ORDER BY ht.id DESC');
    	
		$query->setMaxResults(1);
		 
        $result = $query->getScalarResult();        

        if (count($result) > 0)
        {    
    	    $query = $this->em->createQuery('SELECT ht, mt FROM ModuleContactsBundle:history ht, '.$this->bundle_name.':'.$this->entity_name.' mt 
    	                                     WHERE ht.id_module='.$module->getId().'
                                             AND ht.id>'.$result[0]['ht_id'].'
    	                                     AND ht.id_module_content=mt.id
    	                                     AND mt.id_group='.$sitetree_id.' 
    	                                     ORDER BY ht.id DESC');    	
            $query->setMaxResults(1);
		 
            $result2 = $query->getScalarResult();               

            if (count($result2) > 0)
            {
                return false;
  
            } else return true;
        }
        
        return false;  
        */
        
        return true;
     
    }
}
