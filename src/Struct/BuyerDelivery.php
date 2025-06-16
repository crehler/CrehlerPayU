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

class BuyerDelivery extends PayUStruct
{
    /**
     * Street
     */
    protected string $street;

    /**
     * Postal box
     */
    protected string $postalBox;

    /**
     * Postal code
     */
    protected string $postalCode;

    /**
     * City
     */
    protected string $city;

    /**
     * State
     */
    protected string $state;

    /**
     * Two-letter country code compliant with ISO-3166.
     */
    protected string $countryCode;

    /**
     * Address description
     */
    protected string $name;

    /**
     * Recipient name
     */
    protected string $recipientName;

    /**
     * Recipient email
     */
    protected string $recipientEmail;

    /**
     * Recipient phone number
     */
    protected string $recipientPhone;

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getPostalBox(): string
    {
        return $this->postalBox;
    }

    public function setPostalBox(string $postalBox): self
    {
        $this->postalBox = $postalBox;

        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function setRecipientName(string $recipientName): self
    {
        $this->recipientName = $recipientName;

        return $this;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): self
    {
        $this->recipientEmail = $recipientEmail;

        return $this;
    }

    public function getRecipientPhone(): string
    {
        return $this->recipientPhone;
    }

    public function setRecipientPhone(string $recipientPhone): self
    {
        $this->recipientPhone = $recipientPhone;

        return $this;
    }
}
