<?php

use KskRemoteMaintenance\Services\Authentication;
use Sabre\DAV;

/**
 * Class Shopware_Controllers_Webdav_Index
 */
class Shopware_Controllers_Webdav_Index extends Enlight_Controller_Action
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
    }

    /**
     * @throws DAV\Exception
     * @throws Exception
     */
    public function indexAction()
    {
        $rootDirectory = new DAV\FS\Directory(Shopware()->DocPath());

        // The server object is responsible for making sense out of the WebDAV protocol
        $server = new DAV\Server($rootDirectory);

        // If your server is not on your webroot, make sure the following line has the
        // correct information
        $server->setBaseUri(implode('/', [$this->Request()->getBasePath(), 'webdav', 'index', 'index']));

        $cacheDir = implode(DIRECTORY_SEPARATOR, [
            $this->container->get('kernel')->getCacheDir(),
            'ksk_remote_maintenance'
        ]);
        @mkdir($cacheDir);

        // The lock manager is reponsible for making sure users don't overwrite
        // each others changes.
        $lockBackend = new DAV\Locks\Backend\File(implode(DIRECTORY_SEPARATOR, [$cacheDir, 'locks']));
        $lockPlugin = new DAV\Locks\Plugin($lockBackend);
        $server->addPlugin($lockPlugin);

        /** @var Authentication $authBackend */
        $authBackend = $this->get('ksk_remote_maintenance.services.authentication');
        $authPlugin = new Dav\Auth\Plugin($authBackend);

        // Adding the plugin to the server.
        $server->addPlugin($authPlugin);

        // All we need to do now, is to fire up the server
        $server->exec();

        die;
    }
}
