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

class OrderCreate extends PayUStruct
{
    /**
     * ID of an order used in merchant system
     */
    protected string $extOrderId;

    /**
     * The address for sending notifications
     */
    protected string $notifyUrl;

    /**
     * Payer’s IP address, e.g. 123.123.123.123. Note: 0.0.0.0 is not accepted.
     */
    protected string $customerIp;

    /**
     * Point of sale ID
     */
    protected int $merchantPosId;

    /**
     * Duration for the validity of an order (in seconds), during which time payment must be made
     */
    protected int $validityTime;

    /**
     * Description of the order
     */
    protected string $description;

    /**
     * Additional description of the order
     */
    protected string $additionalDescription;

    /**
     * Currency code compliant with ISO 4217 (e.g EUR).
     */
    protected string $currencyCode;

    /**
     * Total price of the order in pennies (e.g. 1000 is 10.00 EUR).
     * Applies also to currencies without subunits (e.g. 1000 is 10 HUF).
     */
    protected int $totalAmount;

    /**
     * Information about party initializing order:
     * STANDARD_CARDHOLDER - payment is initialized by the card owner;
     * STANDARD_MERCHANT - payment is initialized by the shop, without card owner participation.
     */
    protected string $cardOnFile;

    /**
     * Address for redirecting the customer after payment is commenced.
     * If the payment has not been authorized, error=501 parameter will be added.
     * Please note that no decision regarding payment status should be made depending
     * on the presence or lack of this parameter
     * (to get payment status, wait for notification or retrieve order details).
     */
    protected string $continueUrl;

    /**
     * Section containing buyer data. This information is not required, but it is strongly recommended to include it.
     * Otherwise, the buyer will be prompted to provide missing data on PayU page and payment
     * via Installments or Pay later will not be possible.
     */
    protected Buyer $buyer;

    /**
     * Section containing data of the ordered products. Section products is an array of objects of type Product
     *
     * @var array|Product[]
     */
    protected array $products;

    /**
     * Section allows to directly invoke payment method.
     */
    protected ?PayMethods $payMethods = null;

    /**
     * Section allows to pass currency conversion details.
     */
    protected mixed $mcpData;

    public function getExtOrderId(): string
    {
        return $this->extOrderId;
    }

    public function setExtOrderId(string $extOrderId): self
    {
        $this->extOrderId = $extOrderId;

        return $this;
    }

    public function getNotifyUrl(): string
    {
        return $this->notifyUrl;
    }

    public function setNotifyUrl(string $notifyUrl): self
    {
        $this->notifyUrl = $notifyUrl;

        return $this;
    }

    public function getCustomerIp(): string
    {
        return $this->customerIp;
    }

    public function setCustomerIp(string $customerIp): self
    {
        $this->customerIp = $customerIp;

        return $this;
    }

    public function getMerchantPosId(): int
    {
        return $this->merchantPosId;
    }

    public function setMerchantPosId(int $merchantPosId): self
    {
        $this->merchantPosId = $merchantPosId;

        return $this;
    }

    public function getValidityTime(): int
    {
        return $this->validityTime;
    }

    public function setValidityTime(int $validityTime): self
    {
        $this->validityTime = $validityTime;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAdditionalDescription(): string
    {
        return $this->additionalDescription;
    }

    public function setAdditionalDescription(string $additionalDescription): self
    {
        $this->additionalDescription = $additionalDescription;

        return $this;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(int $totalAmount): self
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getCardOnFile(): string
    {
        return $this->cardOnFile;
    }

    public function setCardOnFile(string $cardOnFile): self
    {
        $this->cardOnFile = $cardOnFile;

        return $this;
    }

    public function getContinueUrl(): string
    {
        return $this->continueUrl;
    }

    public function setContinueUrl(string $continueUrl): self
    {
        $this->continueUrl = $continueUrl;

        return $this;
    }

    public function getBuyer(): Buyer
    {
        return $this->buyer;
    }

    public function setBuyer(Buyer $buyer): self
    {
        $this->buyer = $buyer;

        return $this;
    }

    public function addProduct(Product $product): self
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * @return array|Product[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @param array|Product[] $products
     *
     * @return OrderCreate
     */
    public function setProducts(array $products): self
    {
        $this->products = $products;

        return $this;
    }

    public function getPayMethods(): ?PayMethods
    {
        return $this->payMethods;
    }

    public function setPayMethods(PayMethods $payMethods): self
    {
        $this->payMethods = $payMethods;

        return $this;
    }

    public function getMcpData(): mixed
    {
        return $this->mcpData;
    }

    public function setMcpData(mixed $mcpData): self
    {
        $this->mcpData = $mcpData;

        return $this;
    }
}
