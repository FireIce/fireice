<?php

namespace fireice\Backend\Tree\Model;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Yaml\Yaml;
use fireice\Backend\Tree\Entity\modulesitetree;
use fireice\Backend\Tree\Entity\messages;
use fireice\Backend\Tree\Entity\history;
use fireice\Backend\Dialogs\Entity\module;
use fireice\Backend\Dialogs\Entity\moduleslink;
use Symfony\Component\HttpFoundation\Request;

Class TreeModel
{
    protected $em, $sess;
    protected $treeChilds = array ();
    protected $request;

    public function __construct(EntityManager $em, $sess, $container)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->sess = $sess;
        $this->container = $container;
    }

    public function getNodeTitle($id)
    {
        for_no_name:

        $query = $this->em->createQuery("
            SELECT 
                md.idd AS id,
                md.table_name,
                md.name
            FROM 
                TreeBundle:modulesitetree tr,
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.type = 'sitetree_node'
            AND md_l.up_tree = tr.idd
            AND md_l.up_module = md.idd
            AND (tr.status = 'active' OR tr.status = 'hidden')
            AND tr.final = 'Y'
            AND tr.idd=:id")->setParameter('id', $id);

        $result = $query->getResult();

        if (count($result) == 0) return false;

        $result = $result[0];

        $query = $this->em->createQuery("
            SELECT 
                stm.plugin_id,
                stm.plugin_type
            FROM 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:modulespluginslink md_pl_l,
                Module".$result['name']."Bundle:".$result['table_name']." stm
            WHERE md_l.up_tree = :up_tree
            AND md_l.up_module = :up_module
            AND md_pl_l.up_link = md_l.id
            AND md_pl_l.up_plugin = stm.idd
            AND stm.plugin_name = 'fireice_node_title'
            AND stm.final = 'Y'
            AND stm.status = 'active'")->setParameters(array (
            'up_tree' => $id,
            'up_module' => $result['id'],
            ));

        $result = $query->getResult();

        if (count($result) > 0) $result = $result[0];
        else return '[Узел без имени]';

        $query = $this->em->createQuery("
            SELECT 
                plg.value
            FROM 
                FireicePlugins".ucfirst($result['plugin_type'])."Bundle:plugin".$result['plugin_type']." plg
            WHERE plg.id = :id")->setParameter('id', $result['plugin_id']);

        $result2 = $query->getSingleResult();

        return $result2['value'];
    }

    public function create($security)//*****
    {
        $languages = $this->container->getParameter('languages');
        $languageDefault = $languages['default'];
        unset($languages['default']);
        $node = new modulesitetree();
        $node->setFinal('Y');
        $node->setUpParent($this->request->get('id'));
        $node->setStatus('hidden');
        $this->em->persist($node);
        $this->em->flush();

        $history = new history();
        $history->setUpUser($security->getToken()->getUser()->getId());
        $history->setUp($node->getId());
        $history->setUpTypeCode('sitetree');
        $history->setActionCode('add_node');
        $this->em->persist($history);
        $this->em->flush();

        $node->setIdd($node->getId());
        $node->setCid($history->getId());
        $this->em->persist($node);
        $this->em->flush();

        $query = $this->em->createQuery("
            SELECT 
                md              
            FROM 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.idd = :id")->setParameter('id', $this->request->get('module_id'));

        $module = $query->getSingleResult();

        $config = $this->container->get('cache')->getModuleConfig($module->getName());

// Получаем список модулей
        $subModules = array ();
        foreach ($config['parameters']['modules'] as $val) {
            $subModules[] = "'".$val['name']."'";
        }

        // Берем из базы одним запросом данные для этих модулей
        $query = $this->em->createQuery("
                SELECT
                    md.idd, md.name
                FROM
                    DialogsBundle:modules md
                WHERE md.final = 'Y'
                AND md.status = 'active'
                AND md.name IN(".implode(',', $subModules).")");

        // Формируем вспомогательный массив с данными модулей
        $subModules = array ();
        foreach ($query->getResult() as $value) {
            $subModules[$value['name']] = $value['idd'];
        }
        // Теперь опять обходим конфиг 
        // Все данные хранятся в $subModules['имя_модуля'] = значение_idd
        foreach ($config['parameters']['modules'] as $value) {
            if ('yes' == $value['multilanguage']) {
                foreach ($languages as $lang => $language) {

                    $modulelink = new moduleslink();
                    $modulelink->setUpTree($node->getIdd());
                    $modulelink->setUpModule($subModules[$value['name']]);
                    $modulelink->setLanguage($lang);
                    $this->em->persist($modulelink);
                }
            } else {
                $modulelink = new moduleslink();
                $modulelink->setUpTree($node->getIdd());
                $modulelink->setUpModule($subModules[$value['name']]);
                $modulelink->setLanguage($languageDefault);
                $this->em->persist($modulelink);
            }
        }
        $this->em->flush();

        return $node->getIdd();
    }

    // Удаление узла и его детей
    public function removeAll($id, $security)
    {
        // Получаем все узлы    
        $query = $this->em->createQuery("
            SELECT 
                tr 
            FROM 
                TreeBundle:modulesitetree tr
            WHERE (tr.status = 'active' OR tr.status = 'hidden')
            AND tr.final = 'Y'");

        $nodes = $query->getResult();

        // Строим массив иерархии
        foreach ($nodes as $node) {
            $this->treeChilds[$node->getIdd()] = array ();
        }
        foreach ($nodes as $node) {
            if ($node->getUpParent() !== null) {
                $this->treeChilds[$node->getUpParent()][] = $node->getIdd();
            }
        }

        // Удаляем узел и всех его потомков
        $all = array_merge(array ($id), $this->getAllChilds($id));

        $idUser = $security->getToken()->getUser()->getId();

        foreach ($all as $node) {
            $this->removeNode($node, $idUser);
        }

        // Чистим куки открытых узлов если нужно
        $showNodes = $this->sess->get('show_nodes', false);

        $inArray = array_search($id, $showNodes);

        if ($inArray !== false) {
            if (count($this->treeChilds[$showNodes[$inArray - 1]]) - 1 > 0) {
                $showNodes = array_slice($showNodes, 0, $inArray);
            } else {
                $showNodes = array_slice($showNodes, 0, $inArray - 1);
            }

            $this->sess->set('show_nodes', $showNodes);
        } else {
            if (in_array($id, $this->treeChilds[$showNodes[count($showNodes) - 1]])) {
                if (count($this->treeChilds[$showNodes[count($showNodes) - 1]]) - 1 <= 0) $showNodes = array_slice($showNodes, 0, -1);

                $this->sess->set('show_nodes', $showNodes);
            }
        }
    }

    // Удаление узла
    public function removeNode($id, $idUser)
    {
        $node = new modulesitetree();
        $node->setFinal('Y');
        $node->setStatus('deleted');
        $node->setIdd($id);
        $this->em->persist($node);
        $this->em->flush();

        $history = new history();
        $history->setUpUser($idUser);
        $history->setUp($node->getId());
        $history->setUpTypeCode('sitetree');
        $history->setActionCode('delete_node');
        $this->em->persist($history);
        $this->em->flush();

        $query = $this->em->createQuery("
            SELECT 
                tr 
            FROM 
                TreeBundle:modulesitetree tr
            WHERE (tr.status = 'active' OR tr.status = 'hidden')
            AND tr.final = 'Y'
            AND tr.eid IS NULL
            AND tr.idd = :id")->setParameter('id', $id);

        $oldNode = $query->getOneOrNullResult();

        $oldNode->setFinal('N');
        $oldNode->setEid($history->getId());
        $this->em->persist($oldNode);
        $this->em->flush();

        $query = $this->em->createQuery("
            UPDATE 
                TreeBundle:modulesitetree st 
            SET st.cid = :cid, st.up_parent = :up_parent 
            WHERE st.idd = :id 
            AND st.final = 'Y' 
            AND st.eid IS NULL
            AND st.status = 'deleted'")->setParameters(array (
            'cid' => $history->getId(),
            'up_parent' => $oldNode->getUpParent(),
            'id' => $id
            ));

        $query->getResult();
    }

    // Возвращает всех потомков
    public function getAllChilds($id)
    {
        $return = array ();

        $childsNode = $this->treeChilds[$id];

        foreach ($childsNode as $child) {
            $return[] = $child;

            $return = array_merge($return, $this->getAllChilds($child));
        }
        return $return;
    }

    public function findAll()
    {
        return $this->em->getRepository('TreeBundle:sitetree')->findAll();
    }

    public function findById($id)
    {
        return $this->em->getRepository('TreeBundle:sitetree')->findOneBy(array ('id' => $id));
    }

    // Возвращает единственных потомков узла id
    public function getChildren($id)
    {
        $return = array ();

        if ($id == 1) {
            $query = $this->em->createQuery("
                SELECT 
                    tr.up_parent AS up_parent,
                    md.table_name AS table,
                    md.name AS bundle,
                    md.idd AS id 
                FROM 
                    TreeBundle:modulesitetree tr, 
                    DialogsBundle:moduleslink md_l, 
                    DialogsBundle:modules md
                WHERE md.final = 'Y'
                AND md.status = 'active'
                AND md_l.up_tree = tr.idd
                AND md_l.up_module = md.idd
                AND tr.status = 'active'
                AND tr.final = 'Y'
                AND md.type='sitetree_node'
                AND tr.idd = 1");

            $result1 = $query->getSingleResult();

            $query = $this->em->createQuery("
                SELECT 
                    plg.value AS title
                FROM 
                    Module".$result1['bundle'].'Bundle:'.$result1['table']." md, 
                    FireicePluginsTextBundle:plugintext plg,
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE md.status = 'active'
            
                AND m_l.up_tree = 1
                AND m_l.up_module = :up_module
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd

                AND md.final = 'Y'
                AND md.plugin_id = plg.id
                AND md.plugin_name = 'fireice_node_title'
                AND md.plugin_type = 'text'")->setParameter('up_module', $result1['id']);

            $result2 = $query->getSingleResult();

            $return[] = array (
                'i' => 1,
                'p' => $result1['up_parent'],
                't' => $result2['title'],
                'c' => ''
            );
        }

        $query = $this->em->createQuery("
            SELECT 
                tr.idd AS sitetree_id, 
                tr.up_parent AS parent_id,
                tr.status AS status,
                md.table_name AS table,
                md.name AS bundle,
                md.idd AS id 
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
            AND md.type='sitetree_node'
            AND tr.idd IN (
                SELECT tr2.idd
                FROM TreeBundle:modulesitetree tr2
                WHERE tr2.up_parent = :up_parent
                AND tr2.final = 'Y'
                AND (tr2.status = 'active' OR tr2.status = 'hidden')
            )")->setParameter('up_parent', $id);

        $result = $query->getResult();

        if (count($result) > 0) {
            $childsNode = array ();

            foreach ($result as $val) {
                $childsNode[$val['sitetree_id']] = array (
                    'sitetree_id' => $val['sitetree_id'],
                    'parent_id' => $val['parent_id'],
                    'hidden' => ($val['status'] == 'hidden' ? '1' : '0'),
                    'name' => '[Узел без имени]'
                );
            }

            // +++ Теперь нужно выдернуть Имя для каждого узла        
            $nodeTypes = array ();

            foreach ($result as $child) {
                if (!isset($nodeTypes[$child['table']])) $nodeTypes[$child['table']] = array (
                        'id' => $child['id'],
                        'bundle' => $child['bundle'],
                        'ids' => array ()
                    );

                $nodeTypes[$child['table']]['ids'][] = $child['sitetree_id'];
            }

            foreach ($nodeTypes as $key => $type) {
                $query = $this->em->createQuery("
                    SELECT 
                        m_l.up_tree AS up_tree,
                        plg.value AS title
                    FROM 
                        Module".$type['bundle'].'Bundle:'.$key." md, 
                        FireicePluginsTextBundle:plugintext plg,
                        DialogsBundle:moduleslink m_l,
                        DialogsBundle:modulespluginslink mp_l
                    WHERE md.status = 'active'
            
                    AND m_l.up_tree IN (".implode(',', $type['ids']).")
                    AND m_l.up_module = :up_module
                    AND m_l.id = mp_l.up_link
                    AND mp_l.up_plugin = md.idd

                    AND md.final = 'Y'
                    AND md.plugin_id = plg.id
                    AND md.plugin_name = 'fireice_node_title'
                    AND md.plugin_type = 'text'")->setParameter('up_module', $type['id']);

                foreach ($query->getResult() as $val) {
                    $childsNode[$val['up_tree']]['name'] = $val['title'];
                }
            }
            // --- Теперь нужно выдернуть Имя для каждого узла                        

            $tmp = array ();

            foreach ($childsNode as $child) {
                $tmp[] = $child['sitetree_id'];
            }

            $query = $this->em->createQuery("
                SELECT 
                    tr.up_parent AS parent_id,
                    COUNT (tr.up_parent) AS number_of
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
                AND md.type='sitetree_node'
                AND tr.idd IN (
                    SELECT tr2.idd
                    FROM TreeBundle:modulesitetree tr2
                    WHERE tr2.up_parent IN(".implode(",", $tmp).")
                    AND tr2.final = 'Y'
                    AND (tr2.status = 'active' OR tr2.status = 'hidden')
                ) 
                GROUP BY tr.up_parent");

            $children = $query->getResult();

            $tmp = array ();

            foreach ($children as $val) {
                $tmp[$val['parent_id']] = $val['number_of'];
            }

            foreach ($childsNode as $child) {
                $return[] = array (
                    'i' => $child['sitetree_id'],
                    'p' => $child['parent_id'],
                    't' => $child['name'],
                    'h' => $child['hidden'],
                    'c' => (isset($tmp[$child['sitetree_id']])) ? $tmp[$child['sitetree_id']] : 0
                );
            }
        }

        if (count($return) > 0) {
            $this->sess->set('show_nodes', $this->showNodes($id));
        }

        return $return;
    }

    // Вычисляет массив открытых узлов по id   
    public function showNodes($id)
    {
        $return = array ($id);

        if ($id != 1) {
            $node = $this->em->getRepository('TreeBundle:modulesitetree')->findOneBy(array ('idd' => $id));

            if (is_object($node)) {
                $return = array_merge($this->showNodes($node->getUpParent()), $return);
            }
        }

        return $return;
    }

    // Возвращает открытые узлы                           
    public function getShowNodes()
    {
        $showNodes = $this->sess->get('show_nodes', false);

        if ($showNodes === false) $showNodes = array ();

        return $showNodes;
    }

    public function contextMenu($id, $acl)
    {
        $modules = $this->getModules($id);

        $return = array ();

        // Определяем можно ли показывать пункт "Редактирование"
        if ($acl->checkUserTreePermissions(false, MaskBuilder::MASK_EDIT)) {
            $return[] = array ('title' => 'Редактировать', 'action' => 'edit', 'id' => $id);
        } else {
            foreach ($this->getNodeModules($id, $acl, 'edit') as $val) {
                if ($val['type_module'] == 'user') {
                    $return[] = array ('title' => 'Редактировать', 'action' => 'edit', 'id' => $id);

                    break;
                }
            }
        }

        // Определяем можно ли показывать пункт "Создание потомка"
        if ($acl->checkUserTreePermissions(false, MaskBuilder::MASK_CREATE)) {
            if (count($modules) > 0) {
                $return[] = array ('title' => 'Добавить потомка', 'action' => 'create', 'id' => $id);
            }
        }

        // Определяем можно ли показывать пункт "Удаление узла"
        if ($id !== '1' && $acl->checkUserTreePermissions(false, MaskBuilder::MASK_DELETE)) {
            $return[] = array ('title' => 'Удалить раздел', 'action' => 'remove', 'id' => $id);
        }

        // Определяем можно ли показывать пункт "Права доступа"
        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('editnodesrights'))) {
            $return[] = array ('title' => 'Права доступа', 'action' => 'rights', 'id' => $id);
        }

        // Определяем можно ли показывать пункты "Скрыть узел" и "Открыть узел"
        if ($acl->checkUserTreePermissions(false, $acl->getValueMask('shownodes')) ||
            $acl->checkUserTreePermissions(false, $acl->getValueMask('hidenodes'))) {
            $query = $this->em->createQuery("
                SELECT 
                    tr
                FROM 
                    TreeBundle:modulesitetree tr
                WHERE (tr.status = 'active' OR tr.status = 'hidden')
                AND tr.final = 'Y'
                AND tr.idd = :id")->setParameter('id', $id);

            $result = $query->getOneOrNullResult();

            if (null !== $result) {
                if ($result->getStatus() == 'active' && $acl->checkUserTreePermissions(false, $acl->getValueMask('hidenodes'))) $return[] = array ('title' => 'Скрыть узел', 'action' => 'hidenode', 'id' => $id);
                if ($result->getStatus() == 'hidden' && $acl->checkUserTreePermissions(false, $acl->getValueMask('shownodes'))) $return[] = array ('title' => 'Открыть узел', 'action' => 'shownode', 'id' => $id);
            }
        }

        if (count($return) == 0) {
            $return[] = array ('title' => ' Нет прав ', 'action' => '', 'id' => 0);
        }

        return $return;
    }

    // Возвращает массив модулей которые можно привязать к потомку данного узла
    public function getModules($node_id)
    {
        $returnModules = array ();

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
            AND (tr.status = 'active' OR tr.status = 'hidden')
            AND tr.final = 'Y'
            AND tr.idd = :id
            AND md.type = 'user'
            ORDER BY md.type")->setParameter('id', $node_id);

        $nodeModules = array ();

        foreach ($query->getResult() as $val) {
            $config = $this->container->get('cache')->getConfig($val['name']);

            $nodeModules[] = array (
                'name' => $config['parameters']['name']
            );
        }

        $query = $this->em->createQuery("
            SELECT 
                md.idd AS id,
                md.name AS name
            FROM 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.type='user'");

        $user_modules = $query->getResult();

        $nameTitle = array ();

        foreach ($nodeModules as $key => $node_module) {
            $returnModules[$key] = array ();

            foreach ($user_modules as $user_module) {
                $config = $this->container->get('cache')->getModuleConfig($user_module['name']);

                if (!isset($nameTitle[$config['parameters']['name']])) {
                    $nameTitle[$config['parameters']['name']] = array (
                        'id' => $user_module['id'],
                        'title' => $config['parameters']['title']
                    );
                }

                foreach ($config['parameters']['parent'] as $parent => $v) {
                    if ($parent == $node_module['name']) {
                        // Смотрим не превышены ли уже максимальные количества (count и count-per-parent)	                	
                        $query = $this->em->createQuery("
                            SELECT 
                                count(tr) AS cnt
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
                            AND md.idd = :idd")->setParameter('idd', $user_module['id']);

                        $count = $query->getSingleResult();
                        $count = $count['cnt'];

                        $query = $this->em->createQuery("
                            SELECT 
                                count(tr) AS cnt
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
                            AND tr.up_parent = :up_parent
                            AND md.idd = :idd")->setParameters(array (
                            'up_parent' => $node_id,
                            'idd' => $user_module['id']
                            ));

                        $countPerParent = $query->getSingleResult();
                        $countPerParent = $count['cnt'];

                        if ($count < $config['parameters']['count'] && $countPerParent < $config['parameters']['count-per-parent']) {
                            $returnModules[$key][] = $config['parameters']['name'];
                        }
                    }
                }
            }
        }

        // Считаем пересечение (потом можно переделать)
        $count = count($returnModules);

        if ($count === 1) {
            $result = $returnModules[0];
        } elseif ($count === 2) {
            $result = array_intersect($returnModules[0], $returnModules[1]);
        } elseif ($count === 3) {
            $result = array_intersect($returnModules[0], $returnModules[1], $returnModules[2]);
        }

        $return = array ();

        foreach ($result as $val) {
            $return[$nameTitle[$val]['id']] = $nameTitle[$val]['title'];
        }

        //print_r($return); exit;

        return $return;
    }

    // Подтвердить на уровне редактора
    public function proveEditor($security) //*****
    {
        $query = $this->em->createQuery("
            SELECT 
                md              
            FROM 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.idd = :idd")->setParameter('idd', $this->request->get('id_module'));

        $module = $query->getSingleResult();

        $moduleObj = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
        $moduleObj = new $moduleObj();

        foreach ($moduleObj->getConfig() as $plugin) {
            // Находим старую запись 
            $query = $this->em->createQuery("
                SELECT 
                     md.idd,
                     md.plugin_id,
                     md.cid
                  FROM 
                     Module".$module->getName().'Bundle:'.$module->getTableName()." md,
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE md.eid IS NULL            
                AND m_l.up_tree = :up_tree
                AND m_l.up_module = :up_module
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd
                AND md.final = 'W'
                AND md.status = 'sendtoproveeditor'
                AND md.plugin_name = :plugin_name
                AND md.plugin_type = :plugin_type");

            $query->setParameters(array (
                'up_tree' => $this->request->get('id'),
                'up_module' => $this->request->get('id_module'),
                'plugin_name' => $plugin['name'],
                'plugin_type' => $plugin['type']
            ));

            $result = $query->getResult();

            if (count($result) > 0) {
                // Меняем старую запись и вставляем новую
                $result = $result[0];

                $old_cid = $result['cid'];
                $plugin_id = $result['plugin_id'];

                $history = new history();
                $history->setUpUser($security->getToken()->getUser()->getId());
                $history->setUp($result['idd']);
                $history->setUpTypeCode($module->getTableName());
                $history->setActionCode('prove_editor');
                $this->em->persist($history);
                $this->em->flush();

                $hid = $history->getId();

                $query = $this->em->createQuery("
                    UPDATE 
                        Module".$module->getName().'Bundle:'.$module->getTableName()." md 
                    SET md.final='N', md.eid = :eid 
                    WHERE md.idd = :idd 
                    AND md.final = 'W' 
                    AND md.status = 'sendtoproveeditor'");

                $query->setParameters(array (
                    'eid' => $hid,
                    'idd' => $result['idd']
                ));

                $query->getResult();

                $newModuleRecord = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
                $newModuleRecord = new $newModuleRecord();
                $newModuleRecord->setIdd($result['idd']);
                $newModuleRecord->setCid($hid);
                $newModuleRecord->setFinal('W');
                $newModuleRecord->setPluginId($plugin_id);
                $newModuleRecord->setPluginType($plugin['type']);
                $newModuleRecord->setPluginName($plugin['name']);
                $newModuleRecord->setStatus('proveeditor');
                $this->em->persist($newModuleRecord);
                $this->em->flush();
            } else return 'no_send';
        }

        // Отправляем письмо тому, кто отправлял на утверждение        
        $historyRecord = $this->em->getRepository('TreeBundle:history')->findOneBy(array ('id' => $old_cid));

        $currentUser = $security->getToken()->getUser();

        $subject = 'Материал утверждён.';
        $message = 'Пользователем '.$currentUser->getLogin().' утверждён материал, отправленный вами ему на утверждение. Ссылка на материал: http://localhost/app_dev.php/backoffice/#action/node_edit/id/'.$this->request->get('id').'/type/'.$this->request->get('id_module');

        $this->sendMessage($currentUser->getId(), $historyRecord->getUpUser(), $subject, $message);

        return 'ok';
    }

    // Подтвердить на уровне главного редактора
    public function proveMainEditor($security) //*****
    {
        $query = $this->em->createQuery("
            SELECT 
                md              
            FROM 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.idd = :idd")->setParameter('idd', $this->request->get('id_module'));

        $module = $query->getSingleResult();

        $moduleObj = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
        $moduleObj = new $moduleObj();

        foreach ($moduleObj->getConfig() as $plugin) {
            // Находим старую запись 
            $query = $this->em->createQuery("
                SELECT 
                     md.idd,
                     md.plugin_id,
                     md.cid
                  FROM 
                     Module".$module->getName().'Bundle:'.$module->getTableName()." md,
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE md.eid IS NULL            
                AND m_l.up_tree = :up_tree
                AND m_l.up_module = :up_module
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd
                AND md.final = 'W'
                AND md.status = 'sendtoprovemaineditor'
                AND md.plugin_name = :plugin_name
                AND md.plugin_type = :plugin_type");

            $query->setParameters(array (
                'up_tree' => $this->request->get('id'),
                'up_module' => $this->request->get('id_module'),
                'plugin_name' => $plugin['name'],
                'plugin_type' => $plugin['type']
            ));

            $result = $query->getResult();

            if (count($result) > 0) {
                // Меняем старую запись и вставляем новую
                $result = $result[0];

                $old_cid = $result['cid'];
                $plugin_id = $result['plugin_id'];

                $history = new history();
                $history->setUpUser($security->getToken()->getUser()->getId());
                $history->setUp($result['idd']);
                $history->setUpTypeCode($module->getTableName());
                $history->setActionCode('prove_maineditor');
                $this->em->persist($history);
                $this->em->flush();

                $hid = $history->getId();

                $query = $this->em->createQuery("
                    UPDATE 
                        Module".$module->getName().'Bundle:'.$module->getTableName()." md 
                    SET md.final='N', md.eid = :eid 
                    WHERE md.idd = :idd 
                    AND md.final = 'W' 
                    AND md.status = 'sendtoprovemaineditor'");

                $query->setParameters(array (
                    'eid' => $hid,
                    'idd' => $result['idd']
                ));

                $query->getResult();

                $query = $this->em->createQuery("
                    UPDATE 
                        Module".$module->getName().'Bundle:'.$module->getTableName()." md 
                    SET md.final='N' 
                    WHERE md.idd = :idd 
                    AND md.final = 'Y'")->setParameter('idd', $result['idd']);
                $query->getResult();

                $newModuleRecord = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
                $newModuleRecord = new $newModuleRecord();
                $newModuleRecord->setIdd($result['idd']);
                $newModuleRecord->setCid($hid);
                $newModuleRecord->setFinal('Y');
                $newModuleRecord->setPluginId($plugin_id);
                $newModuleRecord->setPluginType($plugin['type']);
                $newModuleRecord->setPluginName($plugin['name']);
                $newModuleRecord->setStatus('active');
                $this->em->persist($newModuleRecord);
                $this->em->flush();
            } else return 'no_send';
        }

        // Отправляем письмо тому редактору, кто отправлял на утверждение        
        $historyRecord = $this->em->getRepository('TreeBundle:history')->findOneBy(array ('id' => $old_cid));

        $currentUser = $security->getToken()->getUser();

        $subject = 'Материал утверждён.';
        $message = 'Пользователем '.$currentUser->getLogin().' утверждён материал, отправленный вами ему на утверждение. Ссылка на материал: http://localhost/app_dev.php/backoffice/#action/node_edit/id/'.$this->request->get('id').'/type/'.$this->request->get('id_module');

        $this->sendMessage($currentUser->getId(), $historyRecord->getUpUser(), $subject, $message);

        return 'ok';
    }

    // Отправить на подтверждение редактору   
    public function sendToProveEditor($security, $acl) //*****
    {
        $query = $this->em->createQuery("
            SELECT 
                md              
            FROM 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.idd = :idd")->setParameter('idd', $this->request->get('id_module'));

        $module = $query->getSingleResult();

        $moduleObj = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
        $moduleObj = new $moduleObj();

        foreach ($moduleObj->getConfig() as $plugin) {
            // Находим старую запись 
            $query = $this->em->createQuery("
                SELECT 
                     md.idd,
                     md.plugin_id
                  FROM 
                     Module".$module->getName().'Bundle:'.$module->getTableName()." md,
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE md.eid IS NULL            
                AND m_l.up_tree = :up_tree
                AND m_l.up_module = :up_module
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd
                AND md.final != 'N'
                AND md.status = 'edit'
                AND md.plugin_name = :plugin_name
                AND md.plugin_type = :plugin_type");

            $query->setParameters(array (
                'up_tree' => $this->request->get('id'),
                'up_module' => $this->request->get('id_module'),
                'plugin_name' => $plugin['name'],
                'plugin_type' => $plugin['type']
            ));

            $result = $query->getResult();

            if (count($result) > 0) {
                // Меняем старую запись и вставляем новую
                $result = $result[0];

                $plugin_id = $result['plugin_id'];

                $history = new history();
                $history->setUpUser($security->getToken()->getUser()->getId());
                $history->setUp($result['idd']);
                $history->setUpTypeCode($module->getTableName());
                $history->setActionCode('sendtoprove_editor');
                $this->em->persist($history);
                $this->em->flush();

                $hid = $history->getId();

                $query = $this->em->createQuery("
                    UPDATE 
                        Module".$module->getName().'Bundle:'.$module->getTableName()." md 
                    SET md.final='N', md.eid = :eid 
                    WHERE md.idd = :idd 
                    AND md.final = 'W'");

                $query->setParameters(array (
                    'eid' => $hid,
                    'idd' => $result['idd']
                ));

                $query->getResult();

                $newModuleRecord = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
                $newModuleRecord = new $newModuleRecord();
                $newModuleRecord->setIdd($result['idd']);
                $newModuleRecord->setCid($hid);
                $newModuleRecord->setFinal('W');
                $newModuleRecord->setPluginId($plugin_id);
                $newModuleRecord->setPluginType($plugin['type']);
                $newModuleRecord->setPluginName($plugin['name']);
                $newModuleRecord->setStatus('sendtoproveeditor');
                $this->em->persist($newModuleRecord);
                $this->em->flush();
            } else return 'no_save';
        }

        // Отправляем письма всем редакторам
        $tmp = $this->em->getRepository('DialogsBundle:groups')->findAll();
        foreach ($tmp as $group) {
            $groups[$group->getId()] = $group;
        }

        $module = $this->em->getRepository('DialogsBundle:modules')->findOneBy(array ('idd' => $this->request->get('id_module'), 'final' => 'Y'));
        $users = $this->em->getRepository('DialogsBundle:users')->findAll();
        $currentUser = $security->getToken()->getUser();

        $service_module = new module();
        $service_module->setId($module->getId());

        foreach ($users as $user) {
            //$user_group = $groups[$user->getGroups()];

            $group = new RoleSecurityIdentity('group_'.$user->getGroups());

            if ($acl->checkGroupPermissions($service_module, $group, $acl->getValueMask('proveeditor'))) {
                $subject = 'Отправлен материал на утверждение.';
                $message = 'Пользователем '.$currentUser->getLogin().' отправлен материал на утверждение. Ссылка на материал: http://localhost/app_dev.php/backoffice/#action/node_edit/id/'.$this->request->get('id').'/type/'.$this->request->get('id_module');

                $this->sendMessage($currentUser->getId(), $user->getId(), $subject, $message);
            }
        }

        return 'ok';
    }

    // Отправить на подтверждение главному редактору
    public function sendToProveMainEditor($security, $acl) //*****
    {
        $query = $this->em->createQuery("
            SELECT 
                md              
            FROM 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.idd = :idd")->setParameter('idd', $this->request->get('id_module'));

        $module = $query->getSingleResult();

        $moduleObj = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
        $moduleObj = new $moduleObj();

        foreach ($moduleObj->getConfig() as $plugin) {
            // Находим старую запись 
            $query = $this->em->createQuery("
                SELECT 
                     md.idd,
                     md.plugin_id
                  FROM 
                     Module".$module->getName().'Bundle:'.$module->getTableName()." md,
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE md.eid IS NULL            
                AND m_l.up_tree = :up_tree
                AND m_l.up_module = :up_module
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd
                AND md.final != 'N'
                AND (md.status = 'proveeditor' OR md.status = 'edit')
                AND md.plugin_name = :plugin_name
                AND md.plugin_type = :plugin_type");

            $query->setParameters(array (
                'up_tree' => $this->request->get('id'),
                'up_module' => $this->request->get('id_module'),
                'plugin_name' => $plugin['name'],
                'plugin_type' => $plugin['type']
            ));

            $result = $query->getResult();

            if (count($result) > 0) {
                // Меняем старую запись и вставляем новую
                $result = $result[0];

                $plugin_id = $result['plugin_id'];

                $history = new history();
                $history->setUpUser($security->getToken()->getUser()->getId());
                $history->setUp($result['idd']);
                $history->setUpTypeCode($module->getTableName());
                $history->setActionCode('sendtoprove_maineditor');
                $this->em->persist($history);
                $this->em->flush();

                $hid = $history->getId();

                $query = $this->em->createQuery("
                    UPDATE 
                        Module".$module->getName().'Bundle:'.$module->getTableName()." md 
                    SET md.final='N', md.eid = :eid 
                    WHERE md.idd = :idd 
                    AND md.final = 'W'");

                $query->setParameters(array (
                    'eid' => $hid,
                    'idd' => $result['idd']
                ));

                $query->getResult();

                $newModuleRecord = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
                $newModuleRecord = new $newModuleRecord();
                $newModuleRecord->setIdd($result['idd']);
                $newModuleRecord->setCid($hid);
                $newModuleRecord->setFinal('W');
                $newModuleRecord->setPluginId($plugin_id);
                $newModuleRecord->setPluginType($plugin['type']);
                $newModuleRecord->setPluginName($plugin['name']);
                $newModuleRecord->setStatus('sendtoprovemaineditor');
                $this->em->persist($newModuleRecord);
                $this->em->flush();
            } else return 'no_save';
        }

        // Отправляем письма всем главным редакторам
        $tmp = $this->em->getRepository('DialogsBundle:groups')->findAll();
        foreach ($tmp as $group) {
            $groups[$group->getId()] = $group;
        }

        $module = $this->em->getRepository('DialogsBundle:modules')->findOneBy(array ('idd' => $this->request->get('id_module'), 'final' => 'Y'));
        $users = $this->em->getRepository('DialogsBundle:users')->findAll();
        $currentUser = $security->getToken()->getUser();

        $service_module = new module();
        $service_module->setId($module->getId());

        foreach ($users as $user) {
            //$user_group = $groups[$user->getGroups()];

            $group = new RoleSecurityIdentity('group_'.$user->getGroups());

            if ($acl->checkGroupPermissions($service_module, $group, $acl->getValueMask('provemaineditor'))) {
                $subject = 'Отправлен материал на утверждение.';
                $message = 'Пользователем '.$currentUser->getLogin().' отправлен материал на утверждение. Ссылка на материал: http://localhost/app_dev.php/backoffice/#action/node_edit/id/'.$this->request->get('id').'/type/'.$this->request->get('id_module');

                $this->sendMessage($currentUser->getId(), $user->getId(), $subject, $message);
            }
        }

        return 'ok';
    }

    // Вернуть на доработку писателю (рядовому журналисту)
    public function returnWriter($security) //*****
    {
        $query = $this->em->createQuery("
            SELECT 
                md              
            FROM 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.idd = :idd")->setParameter('idd', $this->request->get('id_module'));

        $module = $query->getSingleResult();

        $moduleObj = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
        $moduleObj = new $moduleObj();

        foreach ($moduleObj->getConfig() as $plugin) {
            // Находим старую запись 
            $query = $this->em->createQuery("
                SELECT 
                     md.idd,
                     md.plugin_id,
                     md.cid
                  FROM 
                     Module".$module->getName().'Bundle:'.$module->getTableName()." md,
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE md.eid IS NULL            
                AND m_l.up_tree = :up_tree
                AND m_l.up_module = :up_module
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd
                AND md.final = 'W'
                AND md.status = 'sendtoproveeditor'
                AND md.plugin_name = :plugin_name
                AND md.plugin_type = :plugin_type");

            $query->setParameters(array (
                'up_tree' => $this->request->get('id'),
                'up_module' => $this->request->get('id_module'),
                'plugin_name' => $plugin['name'],
                'plugin_type' => $plugin['type']
            ));

            $result = $query->getResult();

            if (count($result) > 0) {
                // Меняем старую запись и вставляем новую
                $result = $result[0];

                $old_cid = $result['cid'];
                $plugin_id = $result['plugin_id'];

                $history = new history();
                $history->setUpUser($security->getToken()->getUser()->getId());
                $history->setUp($result['idd']);
                $history->setUpTypeCode($module->getTableName());
                $history->setActionCode('return_writer');
                $this->em->persist($history);
                $this->em->flush();

                $hid = $history->getId();

                $query = $this->em->createQuery("
                    UPDATE 
                        Module".$module->getName().'Bundle:'.$module->getTableName()." md 
                    SET md.final='N', md.eid = :eid 
                    WHERE md.idd = :idd 
                    AND md.final = 'W' 
                    AND md.status = 'sendtoproveeditor'");

                $query->setParameters(array (
                    'eid' => $hid,
                    'idd' => $result['idd']
                ));

                $query->getResult();

                $newModuleRecord = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
                $newModuleRecord = new $newModuleRecord();
                $newModuleRecord->setIdd($result['idd']);
                $newModuleRecord->setCid($hid);
                $newModuleRecord->setFinal('W');
                $newModuleRecord->setPluginId($plugin_id);
                $newModuleRecord->setPluginType($plugin['type']);
                $newModuleRecord->setPluginName($plugin['name']);
                $newModuleRecord->setStatus('returnwriter');
                $this->em->persist($newModuleRecord);
                $this->em->flush();
            } else return 'no_send';
        }

        // Отправляем письмо тому, кто отправлял на утверждение        
        $historyRecord = $this->em->getRepository('TreeBundle:history')->findOneBy(array ('id' => $old_cid));

        $currentUser = $security->getToken()->getUser();

        $subject = 'Материал возвращён на доработку.';
        $message = 'Пользователем '.$currentUser->getLogin().' возвращён на доработку материал, 
                    отправленный вами ему на утверждение. 
                    Ссылка на материал: http://localhost/app_dev.php/backoffice/#action/node_edit/id/'.$this->request->get('id').'/type/'.$this->request->get('id_module').'
                    Причина возврата: '.$this->request->get('comment');

        $this->sendMessage($currentUser->getId(), $historyRecord->getUpUser(), $subject, $message);

        return 'ok';
    }

    // Вернуть на доработку редактору
    public function returnEditor($security) //*****
    {
        $query = $this->em->createQuery("
            SELECT 
                md              
            FROM 
                DialogsBundle:modules md
            WHERE md.final = 'Y'
            AND md.status = 'active'
            AND md.idd = :idd")->setParameter('idd', $this->request->get('id_module'));

        $module = $query->getSingleResult();

        $moduleObj = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
        $moduleObj = new $moduleObj();

        foreach ($moduleObj->getConfig() as $plugin) {
            // Находим старую запись 
            $query = $this->em->createQuery("
                SELECT 
                     md.idd,
                     md.plugin_id,
                     md.cid
                  FROM 
                     Module".$module->getName().'Bundle:'.$module->getTableName()." md,
                    DialogsBundle:moduleslink m_l,
                    DialogsBundle:modulespluginslink mp_l
                WHERE md.eid IS NULL            
                AND m_l.up_tree = :up_tree
                AND m_l.up_module = :up_module
                AND m_l.id = mp_l.up_link
                AND mp_l.up_plugin = md.idd
                AND md.final = 'W'
                AND md.status = 'sendtoprovemaineditor'
                AND md.plugin_name = :plugin_name
                AND md.plugin_type = :plugin_type");

            $query->setParameters(array (
                'up_tree' => $this->request->get('id'),
                'up_module' => $this->request->get('id_module'),
                'plugin_name' => $plugin['name'],
                'plugin_type' => $plugin['type']
            ));

            $result = $query->getResult();

            if (count($result) > 0) {
                // Меняем старую запись и вставляем новую
                $result = $result[0];

                $old_cid = $result['cid'];
                $plugin_id = $result['plugin_id'];

                $history = new history();
                $history->setUpUser($security->getToken()->getUser()->getId());
                $history->setUp($result['idd']);
                $history->setUpTypeCode($module->getTableName());
                $history->setActionCode('return_editor');
                $this->em->persist($history);
                $this->em->flush();

                $hid = $history->getId();

                $query = $this->em->createQuery("
                    UPDATE 
                        Module".$module->getName().'Bundle:'.$module->getTableName()." md 
                    SET md.final='N', md.eid = :eid 
                    WHERE md.idd = :idd 
                    AND md.final = 'W' 
                    AND md.status = 'sendtoprovemaineditor'");

                $query->setParameters(array (
                    'eid' => $hid,
                    'idd' => $result['idd']
                ));

                $query->getResult();

                $newModuleRecord = '\\project\\Modules\\'.$module->getName().'\\Entity\\'.$module->getTableName();
                $newModuleRecord = new $newModuleRecord();
                $newModuleRecord->setIdd($result['idd']);
                $newModuleRecord->setCid($hid);
                $newModuleRecord->setFinal('W');
                $newModuleRecord->setPluginId($plugin_id);
                $newModuleRecord->setPluginType($plugin['type']);
                $newModuleRecord->setPluginName($plugin['name']);
                $newModuleRecord->setStatus('returneditor');
                $this->em->persist($newModuleRecord);
                $this->em->flush();
            } else return 'no_send';
        }

        // Отправляем письмо тому, кто отправлял на утверждение        
        $historyRecord = $this->em->getRepository('TreeBundle:history')->findOneBy(array ('id' => $old_cid));

        $currentUser = $security->getToken()->getUser();

        $subject = 'Материал возвращён на доработку.';
        $message = 'Пользователем '.$currentUser->getLogin().' возвращён на доработку материал, 
                    отправленный вами ему на утверждение. 
                    Ссылка на материал: http://localhost/app_dev.php/backoffice/#action/node_edit/id/'.$this->request->get('id').'/type/'.$this->request->get('id_module').'
                    Причина возврата: '.$this->request->get('comment');

        $this->sendMessage($currentUser->getId(), $historyRecord->getUpUser(), $subject, $message);

        return 'ok';
    }

    // Отправляет сообщение
    public function sendMessage($send_from, $send_for, $subject, $message)
    {
        $messages = new messages();
        $messages->setSendFrom($send_from);
        $messages->setSendFor($send_for);
        $messages->setSubject($subject);
        $messages->setMessage($message);
        $messages->setIsRead(0);

        $this->em->persist($messages);
        $this->em->flush();
    }

    // Возвращает массив модулей, которые привязаны к узлу id
    public function getNodeModules($id, $acl, $action = 'edit')
    {
        $query = $this->em->createQuery("
            SELECT 
                md.idd AS id,
                md.name AS name,
                md.type AS type,
                md_l.language as language
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
            ORDER BY md.type DESC")->setParameter('idd', $id);

        $modules = array ();

        foreach ($query->getResult() as $key => $val) {
            $access = false;

            if ($val['type'] == 'sitetree_node') {
                if ($acl->checkUserTreePermissions(false, $acl->getValueMask($action))) {
                    $access = true;
                }
            } else if ($val['type'] == 'user') {

                if ($acl->checkUserPermissions($id, new module($val['id']), false, $acl->getValueMask($action))) {
                    $access = true;
                }
            }

            if ($access) {
                $config = $this->container->get('cache')->getModuleConfig($val['name']);

                $modules[$val['id']][$val['language']] = array (
                    'title' => $config['parameters']['title'],
                    'directory' => $val['name'],
                    'name' => $config['parameters']['name'],
                    'id' => $val['id'],
                    'count' => $config['parameters']['count'],
                    'count-per-parent' => $config['parameters']['count-per-parent'],
                    'parent' => $config['parameters']['parent'],
                    'module_type' => $config['parameters']['type'],
                    'css_tab' => $config['parameters']['css_tab'],
                    'language' => $val['language'],
                );
            }
        }

        return $modules;
    }

    public function getNewMessages($security)
    {
        $query = $this->em->createQuery("
            SELECT 
                COUNT(msg) AS cnt
            FROM 
                TreeBundle:messages msg
            WHERE msg.send_for = :id
            AND msg.is_read = 0")->setParameter('id', $security->getToken()->getUser()->getId());

        $result = $query->getSingleResult();

        return $result['cnt'];
    }

    public function hideNode($id, $security)
    {
        $node = new modulesitetree();
        $node->setFinal('Y');
        $node->setStatus('hidden');
        $node->setIdd($id);
        $this->em->persist($node);
        $this->em->flush();

        $history = new history();
        $history->setUpUser($security->getToken()->getUser()->getId());
        $history->setUp($node->getId());
        $history->setUpTypeCode('sitetree');
        $history->setActionCode('hide_node');
        $this->em->persist($history);
        $this->em->flush();

        $oldNode = $this->em->getRepository('TreeBundle:modulesitetree')->findOneBy(array (
            'idd' => $id,
            'final' => 'Y',
            'status' => 'active',
            'eid' => null
            ));

        $oldNode->setFinal('N');
        $oldNode->setEid($history->getId());
        $this->em->persist($oldNode);
        $this->em->flush();

        $query = $this->em->createQuery("
            UPDATE 
                TreeBundle:modulesitetree st 
            SET st.cid = :cid, st.up_parent = :up_parent 
            WHERE st.idd = :idd 
            AND st.final = 'Y' 
            AND st.eid IS NULL
            AND st.status = 'hidden'");

        $query->setParameters(array (
            'idd' => $id,
            'cid' => $history->getId(),
            'up_parent' => $oldNode->getUpParent()
        ));

        $query->getResult();
    }

    public function showNode($id, $security)
    {
        $node = new modulesitetree();
        $node->setFinal('Y');
        $node->setStatus('active');
        $node->setIdd($id);
        $this->em->persist($node);
        $this->em->flush();

        $history = new history();
        $history->setUpUser($security->getToken()->getUser()->getId());
        $history->setUp($node->getId());
        $history->setUpTypeCode('sitetree');
        $history->setActionCode('show_node');
        $this->em->persist($history);
        $this->em->flush();

        $oldNode = $this->em->getRepository('TreeBundle:modulesitetree')->findOneBy(array (
            'idd' => $id,
            'final' => 'Y',
            'status' => 'hidden',
            'eid' => null
            ));

        $oldNode->setFinal('N');
        $oldNode->setEid($history->getId());
        $this->em->persist($oldNode);
        $this->em->flush();

        $query = $this->em->createQuery("
            UPDATE 
                TreeBundle:modulesitetree st 
            SET st.cid = :cid, st.up_parent = :up_parent 
            WHERE st.idd = :idd 
            AND st.final = 'Y' 
            AND st.eid IS NULL
            AND st.status = 'active'");

        $query->setParameters(array (
            'idd' => $id,
            'cid' => $history->getId(),
            'up_parent' => $oldNode->getUpParent()
        ));

        $query->getResult();
    }

    static function getModuleConfig($name)
    {
        return Yaml::parse($this->container->getParameter('project_modules_directory').'/'.$name.'/Resources/config/config.yml');
    }

}
