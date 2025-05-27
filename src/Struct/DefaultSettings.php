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

namespace Crehler\PayU\Struct;

use Shopware\Core\Framework\Struct\Struct;

class DefaultSettings extends Struct
{
    protected string $orderDescriptionShort;

    protected string $orderDescriptionLong;

    public function __construct()
    {
        $this->orderDescriptionShort = 'Order fee in the online store: {number}';
        $this->orderDescriptionLong = 'Order fee in the best online store.';
    }

    public function getOrderDescriptionShort(): string
    {
        return $this->orderDescriptionShort;
    }

    public function setOrderDescriptionShort(string $orderDescriptionShort): self
    {
        $this->orderDescriptionShort = $orderDescriptionShort;

        return $this;
    }

    public function getOrderDescriptionLong(): string
    {
        return $this->orderDescriptionLong;
    }

    public function setOrderDescriptionLong(string $orderDescriptionLong): self
    {
        $this->orderDescriptionLong = $orderDescriptionLong;

        return $this;
    }
}
