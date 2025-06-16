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

class Buyer extends PayUStruct
{
    /**
     * Payer’s IP address, e.g. 123.123.123.123. Note: 0.0.0.0 is not accepted
     */
    protected string $customerIp;

    /**
     * ID of the customer used in merchant system
     */
    protected string $extCustomerId;

    /**
     * Buyer's email address
     */
    protected string $email;

    /**
     * Buyer's telephone number
     */
    protected string $phone;

    /**
     * Buyer's first name
     */
    protected string $firstName;

    /**
     * Buyer's last name
     */
    protected string $lastName;

    /**
     * National Identification Number
     */
    protected string $nin;

    /**
     * Denotes the language version of PayU hosted payment page and of e-mail messages sent from PayU to the payer
     */
    protected string $language;

    protected BuyerDelivery $delivery;

    public function getCustomerIp(): string
    {
        return $this->customerIp;
    }

    public function setCustomerIp(string $customerIp): self
    {
        $this->customerIp = $customerIp;

        return $this;
    }

    public function getExtCustomerId(): string
    {
        return $this->extCustomerId;
    }

    public function setExtCustomerId(string $extCustomerId): self
    {
        $this->extCustomerId = $extCustomerId;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getNin(): string
    {
        return $this->nin;
    }

    public function setNin(string $nin): self
    {
        $this->nin = $nin;

        return $this;
    }

    public function getLanguage(): string
    {
        if (!in_array($this->language, ['en', 'de', 'pl'], true)) {
            return 'en';
        }

        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getDelivery(): BuyerDelivery
    {
        return $this->delivery;
    }

    public function setDelivery(BuyerDelivery $delivery): self
    {
        $this->delivery = $delivery;

        return $this;
    }
}
