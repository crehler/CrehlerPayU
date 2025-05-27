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

namespace Crehler\PayU\Service\PayU;

use OpenPayU_Order;
use OpenPayuOrderStatus;

class UpdateStatus
{
    /**
     * @throws \OpenPayU_Exception_Configuration
     */
    public function __construct(ConfigurationService $configurationFactor)
    {
        $configurationFactor->initialize();
    }

    /**
     * @throws \OpenPayU_Exception
     */
    public function complete(string $orderID): void
    {
        OpenPayU_Order::statusUpdate([
            'orderId' => $orderID,
            'orderStatus' => OpenPayuOrderStatus::STATUS_COMPLETED,
        ]);
    }
}
