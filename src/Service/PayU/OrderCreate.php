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

use Crehler\PayU\Service\FinalizeTokenGenerator;
use Crehler\PayU\Service\PaymentDetailsReader;
use Crehler\PayU\Struct\Buyer;
use Crehler\PayU\Struct\OrderCreate as OrderStruct;
use Crehler\PayU\Struct\Product;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function round;

class OrderCreate
{
    private ?Request $request;

    /**
     * @throws \OpenPayU_Exception_Configuration
     */
    public function __construct(
        ConfigurationService $configurationFactor,
        private readonly FinalizeTokenGenerator $finalizeTokenGenerator,
        private readonly PaymentDetailsReader $paymentDetailsReader,
        RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $configurationFactor->initialize();
    }

    public function createOrder(
        AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct $paymentTransactionStruct,
        SalesChannelContext $salesChannelContext
    ): OrderStruct {
        $order = new OrderStruct();
        $order = $this->addOrderUrls($order, $paymentTransactionStruct);
        $order = $this->addBasicOrderData($order, $paymentTransactionStruct, $salesChannelContext);
        $order = $this->addProducts($order, $paymentTransactionStruct);
        $order = $this->addBuyer($order, $paymentTransactionStruct, $salesChannelContext);

        return $order;
    }

    private function addOrderUrls(
        OrderStruct $order,
        AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct $paymentTransactionStruct
    ): OrderStruct {
        $order->setNotifyUrl($this->finalizeTokenGenerator->buildUrl($paymentTransactionStruct->getOrderTransaction()));

        if ($paymentTransactionStruct instanceof AsyncPaymentTransactionStruct) {
            $order->setContinueUrl($paymentTransactionStruct->getReturnUrl());
        }

        return $order;
    }

    private function addBasicOrderData(
        OrderStruct $order,
        AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct $paymentTransactionStruct,
        SalesChannelContext $salesChannelContext
    ): OrderStruct {
        $orderNumber = $paymentTransactionStruct->getOrder()->getOrderNumber() ?? '?';

        $order
            ->setExtOrderId($orderNumber . '-' . Uuid::randomHex())
            ->setCustomerIp($this->request->getClientIp())
            ->setMerchantPosId(
                (int) (\OpenPayU_Configuration::getOauthClientId() ?: \OpenPayU_Configuration::getMerchantPosId())
            )
            ->setDescription($this->paymentDetailsReader->generateShortDescription($orderNumber))
            ->setAdditionalDescription($this->paymentDetailsReader->generateLongDescription($orderNumber))
            ->setCurrencyCode($salesChannelContext->getCurrency()->getIsoCode())
            ->setTotalAmount(
                (int) round($paymentTransactionStruct->getOrderTransaction()->getAmount()->getTotalPrice() * 100)
            );

        return $order;
    }

    private function addProducts(
        OrderStruct $order,
        AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct $paymentTransactionStruct
    ): OrderStruct {
        $products = $paymentTransactionStruct->getOrder()->getLineItems()->getElements();

        /** @var OrderLineItemEntity $element */
        foreach ($products as $element) {
            $product = (new Product())
                ->setName($element->getLabel())
                ->setQuantity($element->getQuantity())
                ->setUnitPrice((int) $element->getUnitPrice() * 100)
                ->setVirtual(($element->getType() !== 'product'))
                ->setListingDate($element->getCreatedAt());

            $order->addProduct($product);
        }

        return $order;
    }

    private function addBuyer(
        OrderStruct $order,
        AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct $paymentTransactionStruct,
        SalesChannelContext $salesChannelContext
    ): OrderStruct {
        $customer = $paymentTransactionStruct->getOrder()->getOrderCustomer();

        try {
            $address = $this->paymentDetailsReader
                ->getOrderAddressEntity($paymentTransactionStruct->getOrder()->getBillingAddressId());
        } catch (\Throwable) {
            $address = null;
        }

        $buyer = (new Buyer())
            ->setEmail($customer->getEmail())
            ->setFirstName($customer->getFirstName())
            ->setLastName($customer->getLastName())
            ->setLanguage($this->paymentDetailsReader->getLanguageCode($salesChannelContext));

        if ($address !== null && !empty($address->getPhoneNumber())) {
            $buyer->setPhone($address->getPhoneNumber());
        }

        $order->setBuyer($buyer);

        return $order;
    }
}
