<?php

namespace KskRemoteMaintenance\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Exception;
use Enlight_Controller_Front;
use Enlight_Controller_Plugins_ViewRenderer_Bootstrap;
use Enlight_Event_EventArgs;
use Psr\Log\LoggerInterface;

/**
 * Class Module
 */
class Module implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDir;

    /**
     * @var Enlight_Controller_Front
     */
    private $front;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Module constructor.
     *
     * @param string                   $pluginDir
     * @param Enlight_Controller_Front $front
     * @param LoggerInterface          $logger
     */
    public function __construct($pluginDir, Enlight_Controller_Front $front, LoggerInterface $logger)
    {
        $this->pluginDir = $pluginDir;
        $this->front = $front;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Bootstrap_AfterInitResource_front' => 'addModuleDirectory',
            'Enlight_Controller_Action_PreDispatch_Webdav' => [
                ['autoloadComposer'],
                ['disableViewRenderer'],
            ],
        ];
    }

    /**
     * Adds the controller directory to the dispatchers module directory.
     * This automatically allows for new modules simply by following the
     * naming conventions. It is used to register the custom webdav module.
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function addModuleDirectory(Enlight_Event_EventArgs $args)
    {
        try {
            $this->front->Dispatcher()->addModuleDirectory(implode(DIRECTORY_SEPARATOR, [
                $this->pluginDir,
                'Controllers',
            ]));
        } catch (Enlight_Controller_Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * By including the composer autoload.php file the vendor files are
     * added to the autoloader. This is only done for webdav request to
     * have minimum impact on the performance of all other request.
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function autoloadComposer(Enlight_Event_EventArgs $args)
    {
        require_once implode(DIRECTORY_SEPARATOR, [$this->pluginDir, 'vendor', 'autoload.php']);
    }

    /**
     * This disables the ViewRenderer that would otherwise buffer the
     * output and alter the output in various ways. By setting it to
     * noRenderer the webdav library has complete freedom of the output.
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function disableViewRenderer(Enlight_Event_EventArgs $args)
    {
        /** @var Enlight_Controller_Plugins_ViewRenderer_Bootstrap $viewRenderer */
        $viewRenderer = $this->front->Plugins()->ViewRenderer();
        $viewRenderer->setNoRender();
    }
}
