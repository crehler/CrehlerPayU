<?php

/**
 * @copyright 2019 Crehler Sp. z o. o.
 *
 * https://crehler.com/
 * support@crehler.com
 *
 * This file is part of the PayU plugin for Shopware 6.
 * All rights reserved.
 */

declare(strict_types=1);

namespace Crehler\PayU;

use Crehler\PayU\Util\PluginLifecycle\Activate;
use Crehler\PayU\Util\PluginLifecycle\Deactivate;
use Crehler\PayU\Util\PluginLifecycle\Install;
use Crehler\PayU\Util\PluginLifecycle\Uninstall;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class CrehlerPayU extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        (new Install($this->container, $installContext))->install();
        parent::install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        (new Uninstall($this->container, $uninstallContext))->uninstall();
        parent::uninstall($uninstallContext);
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function activate(ActivateContext $activateContext): void
    {
        (new Activate($this->container, $activateContext))->activate();
        parent::activate($activateContext);
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        (new Deactivate($this->container, $deactivateContext))->deactivate();
        parent::deactivate($deactivateContext);
    }
}
