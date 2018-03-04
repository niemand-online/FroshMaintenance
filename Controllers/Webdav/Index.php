<?php

use KskRemoteMaintenance\Services\Authentication;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Exception;
use Sabre\DAV\FS\Directory;
use Sabre\DAV\Locks\Backend\File;
use Sabre\DAV\Locks\Plugin as LocksPlugin;
use Sabre\DAV\Server;

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
     * @var string
     */
    private $cacheDir;

    /**
     * @inheritdoc
     */
    public function preDispatch()
    {
        $this->pluginDir = $this->container->getParameter('ksk_remote_maintenance.plugin_dir');

        $this->cacheDir = implode(DIRECTORY_SEPARATOR, [
            $this->get('kernel')->getCacheDir(),
            'ksk_remote_maintenance'
        ]);
        @mkdir($this->cacheDir);
    }

    /**
     * @throws Exception
     */
    public function indexAction()
    {
        $this->handleWebdavRequest();
    }

    /**
     * Creates a new webdav server instance and sets it up with the document root,
     * base uri and cache directory. Plugins for file locks and authentication are
     * added and then the server instance will be executed. Because the webdav
     * server sends its own headers, we need to kill the current request afterwards.
     * Otherwise the Enlight / Symfony framework would sabotage some responses.
     *
     * @throws Exception
     */
    public function handleWebdavRequest()
    {
        $server = new Server(new Directory($this->get('application')->DocPath()));
        $server->setBaseUri(implode('/', [$this->Request()->getBasePath(), 'webdav', 'index', 'index']));

        $lockBackend = new File(implode(DIRECTORY_SEPARATOR, [$this->cacheDir, 'locks']));
        $server->addPlugin(new LocksPlugin($lockBackend));

        /** @var Authentication $authBackend */
        $authBackend = $this->get('ksk_remote_maintenance.services.authentication');
        $server->addPlugin(new AuthPlugin($authBackend));

        $server->exec();
        die;
    }
}
