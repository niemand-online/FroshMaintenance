<?php

namespace KskRemoteMaintenance\Services;

use Sabre\DAV\Auth\Backend\AbstractBasic;
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
     * Authentication constructor.
     * @param Shopware_Components_Auth $auth
     */
    public function __construct(Shopware_Components_Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * @inheritdoc
     */
    protected function validateUserPass($username, $password)
    {
        return $this->auth->isPasswordValid($username, $password);
    }
}
