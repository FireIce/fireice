<?php

namespace fireice\Backend\Modules\Model;

use fireice\Backend\Tree\Entity\history;
use fireice\Backend\Dialogs\Entity\module;
use fireice\Backend\Dialogs\Entity\modulespluginslink;
use Symfony\Component\Yaml\Yaml;

class BackendModel extends GeneralModel
{

    public function getBackendData($sitetreeId, $acl, $moduleId, $language = 'ru') // добавить пармаетр язык
    {
        $values = array ();

        foreach ($this->getPlugins() as $plugin) {
            if (!isset($values[$plugin->getValue('type')])) { 
                $values[$plugin->getValue('type')] = $plugin->getData($sitetreeId, $moduleId, $language, $this->getBundleName().':'.$this->getEntityName(), self::TYPE_ITEM); //Передавать язык
            }
        }

        $data = array ();

        foreach ($this->getPlugins() as $plugin) {
            $type = $plugin->getValue('type');

            if (isset($values[$type]) && $values[$type] !== array ()) {
                foreach ($values[$type] as $val) {
                    if ($val['plugin_name'] == $plugin->getValue('name')) {
                        $data[$plugin->getValue('name')] = $plugin->getValues() + array ('value' => $val['plugin_value']);
                        break;
                    }
                }

                if (!isset($data[$plugin->getValue('name')])) $data[$plugin->getValue('name')] = $plugin->getNull();
            } else {
                $data[$plugin->getValue('name')] = $plugin->getNull();
            }
        }

        if (strpos($this->getModuleDir(), 'Fireice') === 0) {
            return array (
                'type' => 'item',
                'data' => $data
            );
        }

        $module = $this->em->getRepository('DialogsBundle:modules')->findOneBy(array ('name' => $this->moduleName, 'final' => 'Y'));

        $serviceModule = new module();
        $serviceModule->setId($module->getId());

        $proveeditor = ($acl->checkUserPermissions($sitetreeId, $serviceModule, false, $acl->getValueMask('proveeditor'))) ? 'show' : 'hide';
        $provemaineditor = ($acl->checkUserPermissions($sitetreeId, $serviceModule, false, $acl->getValueMask('provemaineditor'))) ? 'show' : 'hide';
        $sendtoproveeditor = ($acl->checkUserPermissions($sitetreeId, $serviceModule, false, $acl->getValueMask('sendtoproveeditor'))) ? 'show' : 'hide';
        $sendtoprovemaineditor = ($acl->checkUserPermissions($sitetreeId, $serviceModule, false, $acl->getValueMask('sendtoprovemaineditor'))) ? 'show' : 'hide';
        $returnwriter = ($acl->checkUserPermissions($sitetreeId, $serviceModule, false, $acl->getValueMask('returnwriter'))) ? 'show' : 'hide';
        $returneditor = ($acl->checkUserPermissions($sitetreeId, $serviceModule, false, $acl->getValueMask('returneditor'))) ? 'show' : 'hide';

        // Определение статуса
        $status = null;

        foreach ($values as $val) {
            if (isset($val[0]['status'])) $status = $val[0]['status'];
            else $status = 'active';

            break;
        }

        // Если обычный журналист
        if ($proveeditor == 'hide' && $provemaineditor == 'hide') {
            if ($status == 'sendtoproveeditor' || $status == 'sendtoprovemaineditor') $data = 'send_to_prove';
        }

        // Если редактор
        if ($proveeditor == 'show' || $returnwriter == 'show') {
            if ($status != 'sendtoproveeditor') {
                $proveeditor = 'hide';
                $returnwriter = 'hide';
            }

            if ($status == 'sendtoprovemaineditor') $data = 'send_to_prove';
        }

        // Если главный редактор
        if ($provemaineditor == 'show' || $returneditor == 'show') {
            if ($status != 'sendtoprovemaineditor') {
                $provemaineditor = 'hide';
                $returneditor = 'hide';
            }
        }
        //print_r($data); exit;
        return array (
            'data' => $data,
            'proveeditor' => $proveeditor,
            'provemaineditor' => $provemaineditor,
            'sendtoproveeditor' => $sendtoproveeditor,
            'sendtoprovemaineditor' => $sendtoprovemaineditor,
            'returnwriter' => $returnwriter,
            'returneditor' => $returneditor,
            'language' => $language
        );
    }

    public function createEdit($security, $acl)
    {
        if (strpos($this->getModuleDir(), 'Fireice') === 0) {
            // Если это модуль узла дерева, то обычные действия    
            foreach ($this->getPlugins() as $plugin) {
                $query = $this->em->createQuery("
                    SELECT 
                        md.idd
                    FROM 
                        ".$this->getBundleName().':'.$this->getEntityName()." md,
                        DialogsBundle:moduleslink m_l,
                        DialogsBundle:modulespluginslink mp_l
                    WHERE md.status = 'active'            
                    AND m_l.up_tree = :up_tree
                    AND m_l.up_module = :up_module
                    AND m_l.id = mp_l.up_link
                    AND mp_l.up_plugin = md.idd
                    AND md.final = 'Y'
                    AND md.plugin_name = :plugin_name
                    AND md.plugin_type = :plugin_type");

                $query->setParameters(array (
                    'up_tree' => $this->request->get('id'),
                    'up_module' => $this->request->get('id_module'),
                    'plugin_name' => $plugin->getValue('name'),
                    'plugin_type' => $plugin->getValue('type')
                ));

                $result = $query->getResult();

                if ($result !== array ()) {
                    $result = $result[0];

                    $pluginId = $plugin->setDataInDb($this->request->get($plugin->getValue('name')));

                    $history = new history();
                    $history->setUpUser($security->getToken()->getUser()->getId());
                    $history->setUp($result['idd']);
                    $history->setUpTypeCode($this->getEntityName());
                    $history->setActionCode('edit_record');
                    $this->em->persist($history);
                    $this->em->flush();

                    $hid = $history->getId();

                    $query = $this->em->createQuery("
                        UPDATE 
                            ".$this->getBundleName().':'.$this->getEntityName()." md 
                        SET md.final='N', md.eid = :eid
                        WHERE md.idd = :idd 
                        AND md.final = 'Y' 
                        AND md.eid IS NULL");

                    $query->setParameters(array (
                        'eid' => $hid,
                        'idd' => $result['idd']
                    ));

                    $query->getResult();

                    $newModuleRecord = $this->getModuleEntity();
                    $newModuleRecord->setIdd($result['idd']);
                    $newModuleRecord->setCid($hid);
                    $newModuleRecord->setFinal('Y');
                    $newModuleRecord->setPluginId($pluginId);
                    $newModuleRecord->setPluginType($plugin->getValue('type'));
                    $newModuleRecord->setPluginName($plugin->getValue('name'));
                    $newModuleRecord->setStatus('active');
                    $this->em->persist($newModuleRecord);
                    $this->em->flush();
                } else {
                    $pluginId = $plugin->setDataInDb($this->request->get($plugin->getValue('name')));

                    $newModuleRecord = $this->getModuleEntity();
                    $newModuleRecord->setFinal('T');
                    $newModuleRecord->setPluginId($pluginId);
                    $newModuleRecord->setPluginType($plugin->getValue('type'));
                    $newModuleRecord->setPluginName($plugin->getValue('name'));
                    $newModuleRecord->setStatus('inserting');
                    $this->em->persist($newModuleRecord);
                    $this->em->flush();

                    $history = new history();
                    $history->setUpUser($security->getToken()->getUser()->getId());
                    $history->setUp($newModuleRecord->getId());
                    $history->setUpTypeCode($this->getEntityName());
                    $history->setActionCode('add_record');
                    $this->em->persist($history);
                    $this->em->flush();

                    $newModuleRecord->setIdd($newModuleRecord->getId());
                    $newModuleRecord->setCid($history->getId());
                    $newModuleRecord->setFinal('Y');
                    $newModuleRecord->setStatus('active');
                    $this->em->persist($newModuleRecord);
                    $this->em->flush();

                    $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array (
                        'up_tree' => $this->request->get('id'),
                        'up_module' => $this->request->get('id_module')
                        ));

                    $modulePluginLink = new modulespluginslink();
                    $modulePluginLink->setUpLink($modulelink->getId());
                    $modulePluginLink->setUpPlugin($newModuleRecord->getId());
                    $this->em->persist($modulePluginLink);
                    $this->em->flush();
                }
            }
        } else {
            // Если пользовательский модуль
            $module = $this->em->getRepository('DialogsBundle:modules')->findOneBy(array ('name' => $this->moduleName));

            $serviceModule = new module();
            $serviceModule->setId($module->getId());

            // Смотрим есть ли у пользователя право утверждать статьи на уровне главного редактора        
            if ($acl->checkUserPermissions($this->request->get('id'), $serviceModule, false, $acl->getValueMask('provemaineditor'))) {
                // Если есть    
                foreach ($this->getPlugins() as $plugin) {
                    $query = $this->em->createQuery("
                        SELECT 
                            md.idd
                        FROM 
                            ".$this->getBundleName().':'.$this->getEntityName()." md,
                            DialogsBundle:moduleslink m_l,
                            DialogsBundle:modulespluginslink mp_l
                        WHERE md.eid IS NULL            
                        AND m_l.up_tree = :up_tree
                        AND m_l.up_module = :up_module
                        AND m_l.id = mp_l.up_link
                        AND mp_l.up_plugin = md.idd
                        AND md.final != 'N'
                        AND md.plugin_name = :plugin_name
                        AND md.plugin_type = :plugin_type");

                    $query->setParameters(array (
                        'up_tree' => $this->request->get('id'),
                        'up_module' => $this->request->get('id_module'),
                        'plugin_name' => $plugin->getValue('name'),
                        'plugin_type' => $plugin->getValue('type')
                    ));

                    $result = $query->getResult();

                    if ($result !== array ()) {
                        $result = $result[0];

                        $pluginId = $plugin->setDataInDb($this->request->get($plugin->getValue('name')));

                        $history = new history();
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($result['idd']);
                        $history->setUpTypeCode($this->getEntityName());
                        $history->setActionCode('edit_record');
                        $this->em->persist($history);
                        $this->em->flush();

                        $hid = $history->getId();

                        $query = $this->em->createQuery("UPDATE ".$this->getBundleName().':'.$this->getEntityName()." md SET md.final='N', md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND md.final != 'N'");
                        $query->getResult();

                        $newModuleRecord = $this->getModuleEntity();
                        $newModuleRecord->setIdd($result['idd']);
                        $newModuleRecord->setCid($hid);
                        $newModuleRecord->setFinal('Y');
                        $newModuleRecord->setPluginId($pluginId);
                        $newModuleRecord->setPluginType($plugin->getValue('type'));
                        $newModuleRecord->setPluginName($plugin->getValue('name'));
                        $newModuleRecord->setStatus('active');
                        $this->em->persist($newModuleRecord);
                        $this->em->flush();
                    } else {
                        $pluginId = $plugin->setDataInDb($this->request->get($plugin->getValue('name')));

                        $newModuleRecord = $this->getModuleEntity();
                        $newModuleRecord->setFinal('T');
                        $newModuleRecord->setPluginId($pluginId);
                        $newModuleRecord->setPluginType($plugin->getValue('type'));
                        $newModuleRecord->setPluginName($plugin->getValue('name'));
                        $newModuleRecord->setStatus('inserting');
                        $this->em->persist($newModuleRecord);
                        $this->em->flush();

                        $history = new history();
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($newModuleRecord->getId());
                        $history->setUpTypeCode($this->getEntityName());
                        $history->setActionCode('add_record');
                        $this->em->persist($history);
                        $this->em->flush();

                        $newModuleRecord->setIdd($newModuleRecord->getId());
                        $newModuleRecord->setCid($history->getId());
                        $newModuleRecord->setFinal('Y');
                        $newModuleRecord->setStatus('active');
                        $this->em->persist($newModuleRecord);
                        $this->em->flush();

                        $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array (
                            'up_tree' => $this->request->get('id'),
                            'up_module' => $this->request->get('id_module')
                            ));

                        $modulePluginLink = new modulespluginslink();
                        $modulePluginLink->setUpLink($modulelink->getId());
                        $modulePluginLink->setUpPlugin($newModuleRecord->getId());
                        $this->em->persist($modulePluginLink);
                        $this->em->flush();
                    }
                }
            } else {
                // Если нет
                foreach ($this->getPlugins() as $plugin) {
                    $query = $this->em->createQuery("
                        SELECT 
                            md.idd,
                            md.final 
                        FROM 
                            ".$this->getBundleName().':'.$this->getEntityName()." md,
                            DialogsBundle:moduleslink m_l,
                            DialogsBundle:modulespluginslink mp_l
                        WHERE (md.final = 'Y' OR md.final = 'W')
                        AND md.eid IS NULL
                        AND m_l.up_tree = :up_tree
                        AND m_l.up_module = :up_module
                        AND m_l.id = mp_l.up_link
                        AND mp_l.up_plugin = md.idd
                        AND md.plugin_name = :plugin_name
                        AND md.plugin_type = :plugin_type");

                    $query->setParameters(array (
                        'up_tree' => $this->request->get('id'),
                        'up_module' => $this->request->get('id_module'),
                        'plugin_name' => $plugin->getValue('name'),
                        'plugin_type' => $plugin->getValue('type')
                    ));

                    $result = $query->getResult();

                    if ($result !== array ()) {
                        $result = $result[0];

                        $pluginId = $plugin->setDataInDb($this->request->get($plugin->getValue('name')));

                        $history = new history();
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($result['idd']);
                        $history->setUpTypeCode($this->getEntityName());
                        $history->setActionCode('edit_record');
                        $this->em->persist($history);
                        $this->em->flush();

                        $hid = $history->getId();

                        if ($result['final'] == 'W') $query = $this->em->createQuery("UPDATE ".$this->getBundleName().':'.$this->getEntityName()." md SET md.final='N', md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND (md.final = 'Y' OR md.final = 'W') AND md.eid IS NULL");
                        elseif ($result['final'] == 'Y') $query = $this->em->createQuery("UPDATE ".$this->getBundleName().':'.$this->getEntityName()." md SET md.eid = ".$hid." WHERE md.idd = ".$result['idd']." AND (md.final = 'Y' OR md.final = 'W') AND md.eid IS NULL");
                        $query->getResult();

                        $newModuleRecord = $this->getModuleEntity();
                        $newModuleRecord->setIdd($result['idd']);
                        $newModuleRecord->setCid($hid);
                        $newModuleRecord->setFinal('W');
                        $newModuleRecord->setPluginId($pluginId);
                        $newModuleRecord->setPluginType($plugin->getValue('type'));
                        $newModuleRecord->setPluginName($plugin->getValue('name'));
                        $newModuleRecord->setStatus('edit');
                        $this->em->persist($newModuleRecord);
                        $this->em->flush();
                    }
                    else {
                        $pluginId = $plugin->setDataInDb($this->request->get($plugin->getValue('name')));

                        $newModuleRecord = $this->getModuleEntity();
                        $newModuleRecord->setFinal('T');
                        $newModuleRecord->setPluginId($pluginId);
                        $newModuleRecord->setPluginType($plugin->getValue('type'));
                        $newModuleRecord->setPluginName($plugin->getValue('name'));
                        $newModuleRecord->setStatus('inserting');
                        $this->em->persist($newModuleRecord);
                        $this->em->flush();

                        $history = new history();
                        $history->setUpUser($security->getToken()->getUser()->getId());
                        $history->setUp($newModuleRecord->getId());
                        $history->setUpTypeCode($this->getEntityName());
                        $history->setActionCode('edit_record');
                        $this->em->persist($history);
                        $this->em->flush();

                        $newModuleRecord->setIdd($newModuleRecord->getId());
                        $newModuleRecord->setCid($history->getId());
                        $newModuleRecord->setFinal('W');
                        $newModuleRecord->setStatus('edit');
                        $this->em->persist($newModuleRecord);
                        $this->em->flush();

                        $modulelink = $this->em->getRepository('DialogsBundle:moduleslink')->findOneBy(array (
                            'up_tree' => $this->request->get('id'),
                            'up_module' => $this->request->get('id_module')
                            ));

                        $modulePluginLink = new modulespluginslink();
                        $modulePluginLink->setUpLink($modulelink->getId());
                        $modulePluginLink->setUpPlugin($newModuleRecord->getId());
                        $this->em->persist($modulePluginLink);
                        $this->em->flush();
                    }
                }
            }
        }
    }

    public function getHistory()
    {
        // +++ Главный запрос, получающий список записей в таблице модуля
        $query = $this->em->createQuery("
            SELECT 
                md.id,
                md.plugin_id,
                md.plugin_type,
                md.cid
            FROM 
                ".$this->getBundleName().':'.$this->getEntityName()." md,
                DialogsBundle:moduleslink m_l,
                DialogsBundle:modulespluginslink mp_l
            WHERE md.status = 'active' 
            AND m_l.up_tree = :up_tree
            AND m_l.up_module = :up_module
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd");

        $query->setParameters(array (
            'up_tree' => $this->request->get('id'),
            'up_module' => $this->request->get('id_module')
        ));

        $result = $query->getResult();
        // --- Главный запрос, получающий список записей в таблице модуля
        // +++ Обрабатываем результат и забиваем в массивы
        $tmp = array ();         // Ид плагинов по группам (группы - типы плагинов)
        $tmpCid = array ();     // Параметр cid по группам
        $cids = array ();        // Общий массив параметров cid   

        foreach ($result as $val) {
            if (!isset($tmp[$val['plugin_type']])) $tmp[$val['plugin_type']] = array ();
            $tmp[$val['plugin_type']][] = $val['plugin_id'];

            if (!isset($tmpCid[$val['plugin_type']])) $tmpCid[$val['plugin_type']] = array ();
            $tmpCid[$val['plugin_type']][] = $val['cid'];

            $cids[] = $val['cid'];
        }
        // --- Обрабатываем результат и забиваем в массивы
        // +++ Получаем значения плагинов по группам
        $values = array ();

        foreach ($tmp as $key => $val) {
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
                TreeBundle:history ht
            WHERE ht.id IN (".implode(',', $cids).")");

        $resultCides = $query->getResult();

        $cids = array ();
        $users = array ();

        foreach ($resultCides as $val) {
            $cids[$val['id']] = $val;

            if (!in_array($val['up_user'], $users)) $users[] = $val['up_user'];
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

        $users = array ();

        foreach ($result_users as $val) {
            $users[$val['id']] = $val['login'];
        }
        // --- Получаем массив соответсвия ид юзера - имя юзера
        // Обходим массив values (значения плагинов по группам) и cid (параметр цид по группам) и формируем 
        // историю выдёргивая инфу записи из соответствующего значения cid и записи в массиве $cids
        $plugins = $this->getPlugins();

        $history = array ();

        for ($i = 1; $i <= intval(count($result) / count($plugins)); $i++) {
            $historyValues = array ();

            foreach ($plugins as $plugin) {
                $value = array_pop($values[$plugin->getValue('type')]);
                $cid = array_pop($tmpCid[$plugin->getValue('type')]);

                //$plugin_method = 'get'.ucfirst($plugin->getValue('type'));

                $historyValues[] = array (
                    'title' => $plugin->getValue('title'),
                    'value' => isset($value) ? $value->getValue() : '---',
                );
            }

            if (isset($cids[$cid])) {
                // Для получение инфы по записи (всем плагинам записи) берём cid одной из записи
                $data = $cids[$cid]['date_create'];
                $user = isset($users[$cids[$cid]['up_user']]) ? $users[$cids[$cid]['up_user']] : 'no_data';
                $action_code = $cids[$cid]['action_code'];
            } else {
                $data = 'Нет записи в таблице истории!';
                $user = 'Нет записи в таблице истории!';
                $action_code = 'Нет записи в таблице истории!';
            }

            $history[] = array (
                'data' => $data,
                'user' => $user,
                'action_code' => $action_code,
                'values' => $historyValues,
            );
        }

        return $history;
    }

    // Была ли страница модуля привязанного к узлу отправлена на подтверждение
    public function checkSendProve($sitetreeId)
    { /*
      $module = $this->em->getRepository('RightsBundle:modules')->findOneBy(array('name' => $this->bundle_name));

      $action = $this->em->getRepository('TreeBundle:historyactions')->findOneBy(array('modules_id' => $module->getId(), 'name' => 'sendtoprove'));

      $query = $this->em->createQuery('SELECT ht, mt FROM Tree:history ht, '.$this->bundle_name.':'.$this->entity_name.' mt
      WHERE ht.id_module='.$module->getId().'
      AND ht.id_action='.$action->getId().'
      AND ht.id_module_content=mt.id
      AND mt.id_group='.$sitetreeId.'
      ORDER BY ht.id DESC');

      $query->setMaxResults(1);

      $result = $query->getScalarResult();

      if (count($result) > 0)
      {
      $query = $this->em->createQuery('SELECT ht, mt FROM TreeBundle:history ht, '.$this->bundle_name.':'.$this->entity_name.' mt
      WHERE ht.id_module='.$module->getId().'
      AND ht.id>'.$result[0]['ht_id'].'
      AND ht.id_module_content=mt.id
      AND mt.id_group='.$sitetreeId.'
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

    public function ajaxLoadList($data, $id_item = '')
    {
        if ($data['id_node'] == 0) {
            return array (0 => array (
                    'value' => '---',
                    'checked' => '0'
                ));
        }

        $query = $this->em->createQuery("
            SELECT 
                md.name AS name
            FROM 
                TreeBundle:modulesitetree tr, 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md_l.up_tree = tr.idd
            AND md_l.up_module = md.idd
            AND tr.final = 'Y'
            AND tr.idd = :idd
            AND md.type = 'user'")->setParameter('idd', $data['id_node']);

        $result = $query->getSingleResult();

        $config = Yaml::parse($this->container->getParameter('project_modules_directory').'//'.$result['name'].'//Resources//config//config.yml');

        if ($config['parameters']['type'] !== 'list') {
            return array (0 => array (
                    'value' => '---',
                    'checked' => '0'
                ));
        }

        $query = $this->em->createQuery("
            SELECT 
                md.idd as id_module,
                md.name AS name,
                md.table_name as entity
            FROM 
                TreeBundle:modulesitetree tr, 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md_l.up_tree = tr.idd
            AND md_l.up_module = md.idd
            AND (tr.status = 'active' OR tr.status = 'hidden')
            AND tr.final = 'Y'
            AND tr.idd = :idd
            AND md.type='user'
            ORDER BY md.type")->setParameter('idd', $data['id_node']);

        $nodeModules = $query->getOneOrNullResult();

        $query = $this->em->createQuery("
            SELECT 
                md.row_id,
                plg.value AS plugin_value
            FROM 
                Module".$nodeModules['name'].'Bundle:'.$nodeModules['entity']." md, 
                FireicePlugins".ucfirst($data['plugin_type'])."Bundle:plugin".$data['plugin_type']." plg,
                DialogsBundle:moduleslink m_l,
                DialogsBundle:modulespluginslink mp_l
            WHERE (md.final = 'Y' OR md.final = 'W')
            AND md.eid IS NULL

            AND m_l.up_tree = :up_tree
            AND m_l.up_module = :up_module
            AND m_l.id = mp_l.up_link
            AND mp_l.up_plugin = md.idd

            AND md.plugin_id = plg.id
            AND md.plugin_name = :plugin_name");

        $query->setParameters(array (
            'up_tree' => $data['id_node'],
            'up_module' => $nodeModules['id_module'],
            'plugin_name' => $data['title']
        ));

        $сhoices = array ();

        foreach ($query->getResult() as $val) {
            $сhoices[$val['row_id']] = $val['plugin_value'];
        }

        $return = array ();
        $return[0] = array (
            'value' => '---',
            'checked' => '0'
        );

        foreach ($сhoices as $k => $v) {
            $return[$k] = array (
                'value' => $v,
                'checked' => ($id_item == $k) ? '1' : '0'
            );
        }

        return $return;
    }

}
