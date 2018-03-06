<?php

use GuzzleHttp\Exception\TransferException;
use KskRemoteMaintenance\Services\Authentication;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Exception;
use Sabre\DAV\FS\Directory;
use Sabre\DAV\Locks\Backend\File;
use Sabre\DAV\Locks\Plugin as LocksPlugin;
use Sabre\DAV\Server;
use Shopware\Components\Plugin\ConfigReader;

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
     * @var array
     */
    private $config;

    /**
     * {@inheritdoc}
     */
    public function preDispatch()
    {
        $this->pluginDir = $this->container->getParameter('ksk_remote_maintenance.plugin_dir');

        $this->cacheDir = implode(DIRECTORY_SEPARATOR, [
            $this->get('kernel')->getCacheDir(),
            'ksk_remote_maintenance',
        ]);
        @mkdir($this->cacheDir);

        /** @var ConfigReader $configReader */
        $configReader = $this->get('shopware.plugin.config_reader');
        $this->config = $configReader->getByPluginName('KskRemoteMaintenance');
    }

    /**
     * @throws Exception
     */
    public function indexAction()
    {
        $server = $this->createWebdavServer();
        $fileLocation = realpath($this->getDocumentRoot() . DIRECTORY_SEPARATOR . $server->getRequestUri());

        if ($this->isSafeModeEnabled()) {
            $originalContents = file_get_contents($fileLocation);
        }

        $this->handleWebdavRequest($server);

        if ($this->isSafeModeEnabled() && !$this->isFileReachable($server->getRequestUri())) {
            file_put_contents($fileLocation, $originalContents);
            http_response_code(409);
        }

        die;
    }

    /**
     * Creates a new webdav server instance and sets it up with the document root,
     * base uri and cache directory. Plugins for file locks and authentication are
     * added and then the server instance will be executed. Because the webdav
     * server sends its own headers, we need to kill the current request afterwards.
     * Otherwise the Enlight / Symfony framework would sabotage some responses.
     *
     * @param Server $server
     *
     * @throws Exception
     */
    protected function handleWebdavRequest(Server $server)
    {
        $lockBackend = new File(implode(DIRECTORY_SEPARATOR, [$this->cacheDir, 'locks']));
        $server->addPlugin(new LocksPlugin($lockBackend));

        /** @var Authentication $authBackend */
        $authBackend = $this->get('ksk_remote_maintenance.services.authentication');
        $server->addPlugin(new AuthPlugin($authBackend));

        $server->exec();
    }

    /**
     * @throws Exception
     *
     * @return Server
     */
    protected function createWebdavServer()
    {
        $server = new Server(new Directory($this->getDocumentRoot()));
        $server->setBaseUri(implode('/', [$this->Request()->getBasePath(), 'webdav', 'index', 'index']));

        return $server;
    }

    /**
     * @return string
     */
    protected function getDocumentRoot()
    {
        return $this->get('application')->DocPath() . ltrim($this->config['document_root'], DIRECTORY_SEPARATOR);
    }

    /**
     * @return bool
     */
    protected function isSafeModeEnabled()
    {
        return ((bool) $this->config['safe_mode']) && in_array($this->Request()->getMethod(), [
            'PUT',
            'POST',
            'DELETE',
        ]);
    }

    /**
     * @param string $uri
     *
     * @return bool
     */
    protected function isFileReachable($uri)
    {
        try {
            $response = $this->get('ksk_remote_maintenance.services.webdav_client')->read($uri);
        } catch (TransferException $exception) {
            return false;
        }

        return $response->getStatusCode() === 200;
    }
}
