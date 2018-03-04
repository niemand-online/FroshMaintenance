<?php

namespace KskRemoteMaintenance;

use Enlight_Exception;
use Shopware;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware_Components_Acl;

/**
 * Class KskRemoteMaintenance
 * @package KskRemoteMaintenance
 */
class KskRemoteMaintenance extends Plugin
{
    const HTACCESS_LIMITER_BEGIN = '# BEGIN KskRemoteMaintenance';

    const HTACCESS_LIMITER_END = '# END KskRemoteMaintenance' . PHP_EOL . PHP_EOL;

    const HTACCESS_PREPEND = <<<'HTACCESS'
<IfModule mod_env.c>
SetEnvIf Request_URI "^.*webdav/index/index.*$" SHOPWARE_ENV=KSK_REMOTE_MAINTENANCE
</IfModule>
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteCond %{REQUEST_URI} ^.*webdav/index/index.*$
RewriteRule ^(.*)$ shopware.php [PT,L,QSA]
</IfModule>
HTACCESS;

    const CUSTOM_CONFIG = <<<'CUSTOM_CONFIG'
<?php

$config = include __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

return array_replace_recursive($config, [
    'httpcache' => [
        'enabled' => false,
    ],
]);
CUSTOM_CONFIG;

    const ACL_RESOURCE_NAME = 'ksk_remote_maintenance';

    /**
     * @inheritdoc
     */
    public function install(InstallContext $context)
    {
        $this->restoreHtaccessFile();
        $this->alterHtaccessFile();
        $this->createConfig();
        $this->updateAcl();
    }

    /**
     * @inheritdoc
     */
    public function uninstall(UninstallContext $context)
    {
        $this->restoreHtaccessFile();
        $this->removeConfig();
        $this->deleteAcl();
    }

    /**
     * Get the absolute path to the .htaccess file
     *
     * @return string
     */
    protected function getHtaccessFile()
    {
        /** @var Shopware $application */
        $application = $this->container->get('application');
        return $application->DocPath() . '.htaccess';
    }

    /**
     * Get the custom addition for the .htaccess file including
     * the indicators at the beginning and the end of it.
     *
     * @return string
     */
    protected function getHtaccessCustomContent()
    {
        return implode(PHP_EOL, [
            static::HTACCESS_LIMITER_BEGIN,
            static::HTACCESS_PREPEND,
            static::HTACCESS_LIMITER_END,
        ]);
    }

    /**
     * Prepends the .htaccess file with a custom addition that is
     * necessary so that every webdav request will be handeled by
     * the webdav module and forwarded to the webdav server.
     */
    protected function alterHtaccessFile()
    {
        $htaccessFile = $this->getHtaccessFile();
        $htaccessContent = file_get_contents($htaccessFile);

        if (strpos($htaccessContent, $this->getHtaccessCustomContent()) === false) {
            $htaccessContent = $this->getHtaccessCustomContent() . $htaccessContent;
            file_put_contents($htaccessFile, $htaccessContent);
        }
    }

    /**
     * Restores the .htaccess file to its former state by removing
     * the custom addition from it. This methods searches for the
     * addition by finding the delimiters from this classes constants.
     */
    protected function restoreHtaccessFile()
    {
        $htaccessFile = $this->getHtaccessFile();
        $htaccessContent = file_get_contents($htaccessFile);

        $begin = strpos($htaccessContent, static::HTACCESS_LIMITER_BEGIN);
        $end = strpos($htaccessContent, static::HTACCESS_LIMITER_END) + strlen(static::HTACCESS_LIMITER_END);

        $htaccessContent = substr($htaccessContent, 0, $begin) . substr($htaccessContent, $end);
        file_put_contents($htaccessFile, $htaccessContent);
    }

    /**
     * Creates a new acl resource for the webdav access. Backend users
     * will need to have a role with this resource enabled to be able
     * to access the webdav server.
     *
     * @return bool
     */
    protected function updateAcl()
    {
        /** @var Shopware_Components_Acl $acl */
        $acl = $this->container->get('acl');

        try {
            $acl->createResource(static::ACL_RESOURCE_NAME, ['webdav']);
        } catch (Enlight_Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Removes the acl resource to clean up the system.
     *
     * @return bool
     */
    protected function deleteAcl()
    {
        /** @var Shopware_Components_Acl $acl */
        $acl = $this->container->get('acl');

        return $acl->deleteResource(static::ACL_RESOURCE_NAME);
    }

    /**
     * Creates a custom config file for a dedicated webdav environment.
     * This is necessary to disable the http cache for all webdav requests.
     * If the http cache is not disabled for webdav, head requests will
     * be converted to get requests and the webdav server will handle them
     * incorrectly. This behaviour originates from the symfony http cache.
     */
    protected function createConfig()
    {
        $file = implode(DIRECTORY_SEPARATOR, [$this->container->get('application')->DocPath(), 'config_KSK_REMOTE_MAINTENANCE.php']);
        file_put_contents($file, static::CUSTOM_CONFIG);
    }

    /**
     * Removes the custom config file to clean up the system.
     */
    protected function removeConfig()
    {
        $file = implode(DIRECTORY_SEPARATOR, [$this->container->get('application')->DocPath(), 'config_KSK_REMOTE_MAINTENANCE.php']);
        unlink($file);
    }
}
