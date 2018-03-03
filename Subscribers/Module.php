<?php

namespace KskRemoteMaintenance\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Exception;
use Enlight_Controller_Front;
use Enlight_Controller_Plugins_ViewRenderer_Bootstrap;
use Enlight_Event_EventArgs;

/**
 * Class Module
 * @package KskRemoteMaintenance\Subscribers
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
     * Module constructor.
     * @param string $pluginDir
     * @param Enlight_Controller_Front $front
     */
    public function __construct($pluginDir, Enlight_Controller_Front $front)
    {
        $this->pluginDir = $pluginDir;
        $this->front = $front;
    }

    /**
     * @inheritdoc
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
     * @param Enlight_Event_EventArgs $args
     * @throws Enlight_Controller_Exception
     */
    public function addModuleDirectory(Enlight_Event_EventArgs $args)
    {
        $this->front->Dispatcher()->addModuleDirectory(implode(DIRECTORY_SEPARATOR, [
            $this->pluginDir,
            'Controllers'
        ]));
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function autoloadComposer(Enlight_Event_EventArgs $args)
    {
        require_once implode(DIRECTORY_SEPARATOR, [$this->pluginDir, 'vendor', 'autoload.php']);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function disableViewRenderer(Enlight_Event_EventArgs $args)
    {
        /** @var Enlight_Controller_Plugins_ViewRenderer_Bootstrap $viewRenderer */
        $viewRenderer = $this->front->Plugins()->ViewRenderer();
        $viewRenderer->setNoRender();
    }
}
