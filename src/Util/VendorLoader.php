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

class VendorLoader
{
    private const AUTOLOAD_PATCH = __DIR__ . '/../../vendor/autoload.php';

    public function loadOpenPayU(): void
    {
        if (!class_exists(\OpenPayU_Configuration::class) && is_file(self::AUTOLOAD_PATCH)) {
            require_once self::AUTOLOAD_PATCH;
        }
    }
}
