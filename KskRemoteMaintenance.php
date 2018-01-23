<?php

namespace KskRemoteMaintenance;

use Enlight_Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Shopware;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware_Components_Acl;
use UnexpectedValueException;

/**
 * Class KskRemoteMaintenance
 * @package KskRemoteMaintenance
 */
class KskRemoteMaintenance extends Plugin
{
    const HTACCESS_PREPEND = <<<HTACCESS
# BEGIN KskRemoteMaintenance
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteCond %{REQUEST_URI} (\/KskRemoteMaintenance\/)
RewriteRule ^(.*)$ shopware.php [PT,L,QSA]
</IfModule>
# END KskRemoteMaintenance


HTACCESS;

    const ACL_RESOURCE_NAME = 'ksk_remote_maintenance';

    /**
     * @inheritdoc
     */
    public function install(InstallContext $context)
    {
        $htaccessFile = $this->getHtaccessFile();
        $htaccessContent = file_get_contents($htaccessFile);

        if (strpos($htaccessContent, static::HTACCESS_PREPEND) === false) {
            $htaccessContent = static::HTACCESS_PREPEND . $htaccessContent;
            file_put_contents($htaccessFile, $htaccessContent);
        }

        mkdir($this->getTemporaryDir());
        $this->updateAcl();
    }

    /**
     * @inheritdoc
     */
    public function uninstall(UninstallContext $context)
    {
        $htaccessFile = $this->getHtaccessFile();
        $htaccessContent = file_get_contents($htaccessFile);

        if (substr($htaccessContent, 0, strlen(static::HTACCESS_PREPEND)) === static::HTACCESS_PREPEND) {
            $htaccessContent = substr($htaccessContent, strlen(static::HTACCESS_PREPEND));
            file_put_contents($htaccessFile, $htaccessContent);
        }

        $this->removeTemporaryDir();
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
    protected function getTemporaryDir()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->getPath(), '.tmp']);
    }

    /**
     * @return bool
     */
    protected function removeTemporaryDir()
    {
        try {
            $iterator = new RecursiveDirectoryIterator($this->getTemporaryDir(), RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);
        } catch (UnexpectedValueException $exception) {
            return false;
        }

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        return rmdir($this->getTemporaryDir());
    }

    /**
     * @return bool
     */
    protected function updateAcl()
    {
        /** @var Shopware_Components_Acl $acl */
        $acl = $this->container->get('acl');

        try {
            $acl->createResource(static::ACL_RESOURCE_NAME, [
                'webdav',
            ]);
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
}
