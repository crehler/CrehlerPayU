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

namespace Crehler\PayU\Util;

class TestPaymentConfig
{
    public static function getConfiguration(int $merchantPosId, string $clientIp): array
    {
        return [
            'customerIp' => $clientIp,
            'merchantPosId' => $merchantPosId,
            'description' => 'Check credentials from Shopware',
            'currencyCode' => 'PLN',
            'totalAmount' => '100',
            'products' => [
                [
                    'name' => 'PayU integration for Shopware 6',
                    'unitPrice' => '100',
                    'quantity' => '1',
                ],
            ],
        ];
    }
}
