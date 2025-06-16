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

class Product extends PayUStruct
{
    /**
     * Name of the product
     */
    protected string $name;

    /**
     * Unit price
     */
    protected int $unitPrice;

    /**
     * Quantity
     */
    protected int $quantity;

    /**
     * Product type, which can be virtual or material.
     */
    protected bool $virtual;

    /**
     * Marketplace date from which the product (or offer) is available, for example: "2016-01-26T17:35:37+01:00"
     */
    protected \DateTimeInterface $listingDate;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getVirtual(): bool
    {
        return $this->virtual;
    }

    public function setVirtual(bool $virtual): self
    {
        $this->virtual = $virtual;

        return $this;
    }

    public function getListingDate(): string
    {
        return $this->listingDate->format(\DateTimeInterface::RFC3339);
    }

    public function setListingDate(\DateTimeInterface $listingDate): self
    {
        $this->listingDate = $listingDate;

        return $this;
    }
}
