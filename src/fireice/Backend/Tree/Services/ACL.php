<?php

namespace fireice\Backend\Tree\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use fireice\Backend\Dialogs\Entity\module;

class ACL
{
    // Дополнительные маски
    const MASK_PROVEEDITOR = 1024;        // Подтвердить на уровне редактора
    const MASK_PROVEMAINEDITOR = 2048;        // Подтвердить на уровне главного редактора
    const MASK_SENDTOPROVEEDITOR = 4096;        // Отправить на утверждение редактору
    const MASK_SENDTOPROVEMAINEDITOR = 8192;        // Отправить на утверждение главному редактору
    const MASK_RETURNWRITER = 16384;       // Вернуть на доработку писателю (рядовому журналисту)
    const MASK_RETURNEDITOR = 32768;       // Вернуть на доработку редактору
    const MASK_EDITNODESRIGHTS = 65536;       // Право менять права для разных пользователей-узлов
    const MASK_SHOWNODES = 131072;      // Право открывать узлы
    const MASK_HIDENODES = 262144;      // Право скрывать узлы
    const MASK_SEEHIDENODES = 524288;      // Право смотреть скрытые узлы во фронтенде    
    const MASK_VIEWUSERS = 1048576;       // Смотреть список юзеров
    const MASK_EDITUSER = 2097152;       // Редактировать (добавлять) юзеров
    const MASK_DELETEUSER = 4194304;       // Удалять юзеров
    const MASK_VIEWGROUPS = 8388608;      // Смотреть список групп
    const MASK_EDITGROUP = 16777216;      // Редактировать (добавлять) группы
    const MASK_DELETEGROUP = 33554432;      // Удалять группы    
    const MASK_DELETEITEM = 67108864;       // Удалять запись в узлах типа новостей
    protected $em;
    protected $aclProvider;
    protected $securityContext;
    protected $acl;
    protected $acl_no_rights_array = null;
    public $current_user;
    private $tree_object = null;

    public function __construct(EntityManager $em, $aclProvider, $securityContext)
    {
        $this->em = $em;
        $this->aclProvider = $aclProvider;
        $this->securityContext = $securityContext;

        if ($this->securityContext->getToken()) {
            $this->current_user = $this->securityContext->getToken()->getUser();

            if ($this->current_user == 'anon.') $this->current_user = $this->em->getRepository('DialogsBundle:users')->findOneBy(array ('type' => 'anonymous'));
        }
    }

    public function getAclNoRightsArray($id_user)
    {
        $query = $this->em->createQuery("
            SELECT 
                md_l.up_tree AS id_node,
                md_l.up_module AS id_module,
                acl.not_rights AS not_rights
            FROM 
                DialogsBundle:moduleslink md_l, 
                DialogsBundle:aclnodesrights acl
            WHERE acl.up_modules_link = md_l.id
            AND acl.up_user = :id_user")->setParameter('id_user', $id_user);

        $result = $query->getResult();

        $array = array ();

        foreach ($result as $val) {
            if (!isset($array[$val['id_node']])) $array[$val['id_node']] = array ();

            if (!isset($array[$val['id_node']][$val['id_module']])) $array[$val['id_node']][$val['id_module']] = $val['not_rights'];
        }

        //print_r($array); exit;

        return $array;
    }

    // Присваиваем права группе (для первого запуска)
    public function createPermissionsForGroup($object, $group, $mask=MaskBuilder::MASK_OWNER)
    {
        $securityIdentity = $group;

        $this->getObject($object, $securityIdentity);

        $objectAces = $this->acl->getObjectAces();
        $founded = 0;
        foreach ($objectAces as $index => $ace) {
            if ($securityIdentity->equals($ace->getSecurityIdentity())) {
                $founded++;
                try {
                    $this->acl->updateObjectAce($index, $mask);
                } catch (\OutOfBoundsException $exception) {
                    $this->acl->insertObjectAce($securityIdentity, $mask);
                }
            }
        }
        if ($founded == 0) {
            $this->acl->insertObjectAce($securityIdentity, $mask);
        }

        $this->aclProvider->updateAcl($this->acl);
    }

    // Присваивание привилегии пользователю
    public function createPermissionsForUser($object, $user, $mask=MaskBuilder::MASK_OWNER)
    {
        //$user_group = $this->em->getRepository('DialogsBundle:groups')->findOneBy(array('id' => $user->getGroups()));   

        $securityIdentity = new RoleSecurityIdentity('group_'.$user->getGroups());

        $this->getObject($object, $securityIdentity);

        $objectAces = $this->acl->getObjectAces();

        $founded = 0;
        foreach ($objectAces as $index => $ace) {
            if ($securityIdentity->equals($ace->getSecurityIdentity())) {
                $founded++;
                try {
                    $this->acl->updateObjectAce($index, $mask);
                } catch (\OutOfBoundsException $exception) {
                    $this->acl->insertObjectAce($securityIdentity, $mask);
                }
            }
        }
        if ($founded == 0) {
            $this->acl->insertObjectAce($securityIdentity, $mask);
        }
        $this->aclProvider->updateAcl($this->acl);
    }

    // Присваивание всех прав для всех модулей (в том виде в котором они поступают из формы) разом группе
    public function createManyPermissions(array $module_rights, $group)
    {
        foreach ($module_rights as $key => $rights) {
            $module = new module();
            $module->setId($key);

            $builder = new MaskBuilder();

            foreach ($rights as $right) {
                $builder->add($this->getValueMask($right));
            }

            $this->createPermissionsForGroup($module, $group, $builder->get());
        }
    }

    // Проверка группы
    public function checkGroupPermissions($object, $group, $mask)
    {
        $securityIdentity = $group;

        $this->getObject($object, $securityIdentity);

        $objectAces = $this->acl->getObjectAces();

        foreach ($objectAces as $index => $ace) {
            if ($securityIdentity->equals($ace->getSecurityIdentity()) && ($ace->getMask() & $mask) === $mask) {
                return true;
            }
        }

        return false;
    }

    // Проверка юзера
    public function checkUserPermissions($id_node, $object, $user, $mask)
    {
        if (false === $user) $user = $this->current_user;

        if ($this->acl_no_rights_array === null) {
            $this->acl_no_rights_array = $this->getAclNoRightsArray($user->getId());
        }

        if ($user->getGroups() !== null) {
            $group = new RoleSecurityIdentity('group_'.$user->getGroups());

            if ($this->checkGroupPermissions($object, $group, $mask)) {
                if (isset($this->acl_no_rights_array[$id_node][$object->getId()])) $not_rights = intval($this->acl_no_rights_array[$id_node][$object->getId()]);
                else $not_rights = 0;

                if (($mask & (~$not_rights)) === $mask) {
                    return true;
                }
            }
        }

        return false;
    }

    // Проверка юзера
    public function checkUserTreePermissions($user, $mask)
    {
        if ($this->tree_object === null) {
            $this->tree_object = new module();
            $this->tree_object->setId(-1);
        }

        if (false === $user) $user = $this->current_user;

        if ($user->getGroups() !== null) {
            $group = new RoleSecurityIdentity('group_'.$user->getGroups());

            if ($this->checkGroupPermissions($this->tree_object, $group, $mask)) {
                return true;
            }
        }

        return false;
    }

    public function getObject($object, $securityIdentity)
    {
        $objectIdentity = ObjectIdentity::fromDomainObject($object);
        try {
            $this->acl = $this->aclProvider->findAcl($objectIdentity, array ($securityIdentity));
        } catch (AclNotFoundException $exception) {
            $this->acl = $this->aclProvider->createAcl($objectIdentity);
        }
    }

    public function removePermissionsForModule($module, $group)
    {
        $securityIdentity = $group;

        $this->getObject($module, $securityIdentity);

        $objectAces = $this->acl->getObjectAces();

        foreach ($objectAces as $index => $ace) {
            if ($securityIdentity->equals($ace->getSecurityIdentity())) {
                $this->acl->deleteObjectAce($index);
            }
        }

        $this->aclProvider->updateAcl($this->acl);
    }

    // Удаление всех прав для группы
    public function removeGroupPermissions($modules, $group)
    {
        foreach ($modules as $mod) {
            $module = new module();
            $module->setId($mod['id']);

            $this->removePermissionsForModule($module, $group);
        }
        
        $query = $this->em->getConnection()->prepare("
                DELETE FROM acl_security_identities 
                WHERE identifier = :identifer");

        $tmp = $group->getRole();
        $query->bindParam(':identifer', $tmp, \PDO::PARAM_STR);
        $query->execute();
    }

    public function getValueMask($mask)
    {
        $rc = new \ReflectionClass('Symfony\Component\Security\Acl\Permission\MaskBuilder');

        if (is_integer($rc->getConstant('MASK_'.strtoupper($mask)))) {
            return $rc->getConstant('MASK_'.strtoupper($mask));
        } else {
            return constant('static::MASK_'.strtoupper($mask));
        }
    }

}
