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
     * @return string
     */
    protected function getHtaccessFile()
    {
        /** @var Shopware $application */
        $application = $this->container->get('application');
        return $application->DocPath() . '.htaccess';
    }

    /**
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

    protected function alterHtaccessFile()
    {
        $htaccessFile = $this->getHtaccessFile();
        $htaccessContent = file_get_contents($htaccessFile);

        if (strpos($htaccessContent, $this->getHtaccessCustomContent()) === false) {
            $htaccessContent = $this->getHtaccessCustomContent() . $htaccessContent;
            file_put_contents($htaccessFile, $htaccessContent);
        }
    }

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
     * @return bool
     */
    protected function deleteAcl()
    {
        /** @var Shopware_Components_Acl $acl */
        $acl = $this->container->get('acl');

        return $acl->deleteResource(static::ACL_RESOURCE_NAME);
    }

    protected function createConfig()
    {
        $file = implode(DIRECTORY_SEPARATOR, [$this->container->get('application')->DocPath(), 'config_KSK_REMOTE_MAINTENANCE.php']);
        file_put_contents($file, static::CUSTOM_CONFIG);
    }

    protected function removeConfig()
    {
        $file = implode(DIRECTORY_SEPARATOR, [$this->container->get('application')->DocPath(), 'config_KSK_REMOTE_MAINTENANCE.php']);
        unlink($file);
    }
}
