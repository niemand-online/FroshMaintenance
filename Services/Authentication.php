<?php

namespace KskRemoteMaintenance\Services;

use KskRemoteMaintenance\KskRemoteMaintenance;
use Sabre\DAV\Auth\Backend\AbstractBasic;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\User\Role;
use Shopware\Models\User\User;
use Shopware_Components_Acl;

/**
 * Class Authentication
 * @package KskRemoteMaintenance\Services
 */
class Authentication extends AbstractBasic
{
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
     * @param Shopware_Components_Acl $acl
     * @param ModelManager $modelManager
     */
    public function __construct(
        Shopware_Components_Acl $acl,
        ModelManager $modelManager
    ) {
        $this->acl = $acl;
        $this->modelManager = $modelManager;
    }

    /**
     * @inheritdoc
     */
    protected function validateUserPass($username, $password)
    {
        $repository = $this->modelManager->getRepository(User::class);
        $user = $repository->findOneBy(['username' => $username, 'active' => true]);

        if (!$user) {
            return false;
        }

        $apiKey = $user->getApiKey();

        if (empty($apiKey)) {
            return false;
        }

        /** @var Role $role */
        $role = $this->modelManager->find(Role::class, $user->getRoleId());

        return $apiKey === $password
            && $this->acl->isAllowed($role->getName(), KskRemoteMaintenance::ACL_RESOURCE_NAME, 'webdav');
    }
}
