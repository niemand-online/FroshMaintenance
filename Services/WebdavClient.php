<?php

namespace FroshMaintenance\Services;

use Enlight_Controller_Front;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\ResponseInterface;
use Shopware\Components\HttpClient\GuzzleFactory;
use Shopware\Components\Routing\Router;

class WebdavClient
{
    /**
     * @var GuzzleFactory
     */
    private $guzzleFactory;

    /**
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Enlight_Controller_Front
     */
    private $front;

    /**
     * WebdavClient constructor.
     *
     * @param GuzzleFactory            $guzzleFactory
     * @param Router                   $router
     * @param Enlight_Controller_Front $front
     */
    public function __construct(GuzzleFactory $guzzleFactory, Router $router, Enlight_Controller_Front $front)
    {
        $this->guzzleFactory = $guzzleFactory;
        $this->guzzleClient = $guzzleFactory->createClient();
        $this->router = $router;
        $this->front = $front;
    }

    /**
     * @param string $uri
     *
     * @throws TransferException
     *
     * @return ResponseInterface
     */
    public function read($uri)
    {
        return $this->guzzleClient->get($this->getEndpoint() . $uri, [
            'auth' => $this->getAuth(),
        ]);
    }

    /**
     * @return string
     */
    protected function getEndpoint()
    {
        $endpoint = $this->router->assemble([
            'module' => 'webdav',
            'controller' => 'index',
            'action' => 'index',
            'fullPath' => true,
        ]);

        return rtrim($endpoint, '/') . '/index/index/';
    }

    /**
     * @return array
     */
    protected function getAuth()
    {
        try {
            $authHeader = $this->front->Request()->getHeader('Authorization');
        } catch (Exception $e) {
            return [];
        }

        return explode(':', base64_decode(substr($authHeader, strlen('Basic '))));
    }
}
