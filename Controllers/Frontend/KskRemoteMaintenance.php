<?php

use Sabre\DAV;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Frontend_KskRemoteMaintenance
 */
class Shopware_Controllers_Frontend_KskRemoteMaintenance extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * @var string
     */
    private $pluginDir;

    /**
     * @inheritdoc
     */
    public function preDispatch()
    {
        $this->pluginDir = $this->container->getParameter('ksk_remote_maintenance.plugin_dir');
        require_once implode(DIRECTORY_SEPARATOR, [$this->pluginDir, 'vendor', 'autoload.php']);
    }

    public function webdavAction()
    {
        $rootDirectory = new DAV\FS\Directory(Shopware()->DocPath());

        // The server object is responsible for making sense out of the WebDAV protocol
        $server = new DAV\Server($rootDirectory);

        // If your server is not on your webroot, make sure the following line has the
        // correct information
        $server->setBaseUri(implode('/', [$this->Request()->getBasePath(), 'KskRemoteMaintenance', 'webdav']));

        // The lock manager is reponsible for making sure users don't overwrite
        // each others changes.
        $lockBackend = new DAV\Locks\Backend\File(implode(DIRECTORY_SEPARATOR, [$this->pluginDir, '.tmp', 'locks']));
        $lockPlugin = new DAV\Locks\Plugin($lockBackend);
        $server->addPlugin($lockPlugin);

        // This ensures that we get a pretty index in the browser, but it is
        // optional.
        $server->addPlugin(new DAV\Browser\Plugin());

        // All we need to do now, is to fire up the server
        $server->exec();

        die;
    }

    /**
     * @inheritdoc
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'webdav',
        ];
    }
}
