<?php

namespace KskRemoteMaintenance;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;

/**
 * Class KskRemoteMaintenance
 * @package KskRemoteMaintenance
 */
class KskRemoteMaintenance extends Plugin
{
    /**
     * @inheritdoc
     */
    public function install(InstallContext $context)
    {
        mkdir(implode(DIRECTORY_SEPARATOR, [$this->getPath(), 'data']));
    }
}
