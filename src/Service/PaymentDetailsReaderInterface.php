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

namespace Crehler\PayU\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface PaymentDetailsReaderInterface
{
    public function getLanguageCode(SalesChannelContext $salesChannelContext): string;

    public function getOrderAddressEntity(string $orderAddressID): OrderAddressEntity;

    public function getCountryCode(string $countryID): string;

    public function generateShortDescription(string $orderNumber): string;

    public function generateLongDescription(string $orderNumber): string;
}
