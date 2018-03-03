<?php

namespace KskRemoteMaintenance\Services;

use KskRemoteMaintenance\KskRemoteMaintenance;
use Sabre\DAV\Auth\Backend\AbstractBasic;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\User\Role;
use Shopware_Components_Acl;
use Shopware_Components_Auth;

/**
 * Class Authentication
 * @package KskRemoteMaintenance\Services
 */
class Authentication extends AbstractBasic
{
    /**
     * @var Shopware_Components_Auth
     */
    private $auth;

    /**
     * @var Shopware_Components_Acl
     */
    private $acl;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * Authentication constructor.
     * @param Shopware_Components_Auth $auth
     * @param Shopware_Components_Acl $acl
     * @param ModelManager $modelManager
     */
    public function __construct(
        Shopware_Components_Auth $auth,
        Shopware_Components_Acl $acl,
        ModelManager $modelManager
    ) {
        $this->auth = $auth;
        $this->acl = $acl;
        $this->modelManager = $modelManager;
    }

    /**
     * TODO use api auth
     * @inheritdoc
     */
    protected function validateUserPass($username, $password)
    {
        if (!$this->auth->login($username, $password)->isValid()) {
            return false;
        }

        /** @var Role $role */
        $role = $this->modelManager->find(Role::class, $this->auth->getIdentity()->roleID);
        return $this->acl->isAllowed($role->getName(), KskRemoteMaintenance::ACL_RESOURCE_NAME, 'webdav');
    }
}
