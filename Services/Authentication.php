<?php

namespace KskRemoteMaintenance\Services;

use Sabre\DAV\Auth\Backend\AbstractBasic;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\User\User;
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

        return $apiKey === $password;
    }
}
