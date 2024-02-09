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

namespace Crehler\PayU\Service\PayU;

use Crehler\PayU\Service\FinalizeTokenGenerator;
use Crehler\PayU\Service\PaymentDetailsReader;
use Crehler\PayU\Struct\Buyer;
use Crehler\PayU\Struct\OrderCreate as OrderStruct;
use Crehler\PayU\Struct\Product;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class OrderCreate
 */
class OrderCreate
{
    /** @var Request */
    private $request;

    /** @var FinalizeTokenGenerator */
    private $finalizeTokenGenerator;

    /** @var PaymentDetailsReader */
    private $paymentDetailsReader;

    /**
     * OrderCreate constructor.
     *
     * @param FinalizeTokenGenerator $finalizeTokenGenerator
     * @param PaymentDetailsReader   $paymentDetailsReader
     * @param RequestStack           $request
     * @param ConfigurationService   $configurationFactor
     *
     * @throws \OpenPayU_Exception_Configuration
     */
    public function __construct(FinalizeTokenGenerator $finalizeTokenGenerator, PaymentDetailsReader $paymentDetailsReader, RequestStack $request, ConfigurationService $configurationFactor)
    {
        $this->finalizeTokenGenerator = $finalizeTokenGenerator;
        $this->paymentDetailsReader = $paymentDetailsReader;
        $this->request = $request->getCurrentRequest();
        $configurationFactor->initialize();
    }

    /**
     * @param AsyncPaymentTransactionStruct $asyncPaymentTransactionStruct
     * @param SalesChannelContext           $salesChannelContext
     *
     * @return OrderStruct
     */
    public function createOrder(AsyncPaymentTransactionStruct $asyncPaymentTransactionStruct, SalesChannelContext $salesChannelContext): OrderStruct
    {
        $order = new OrderStruct();
        $order = $this->addOrderUrls($order, $asyncPaymentTransactionStruct);
        $order = $this->addBasicOrderData($order, $asyncPaymentTransactionStruct, $salesChannelContext);
        $order = $this->addProducts($order, $asyncPaymentTransactionStruct);
        $order = $this->addBuyer($order, $asyncPaymentTransactionStruct, $salesChannelContext);

        return $order;
    }

    /**
     * @param OrderStruct                   $order
     * @param AsyncPaymentTransactionStruct $asyncPaymentTransactionStruct
     *
     * @return OrderStruct
     */
    private function addOrderUrls(OrderStruct $order, AsyncPaymentTransactionStruct $asyncPaymentTransactionStruct): OrderStruct
    {
        $order->setNotifyUrl($this->finalizeTokenGenerator->buildUrl($asyncPaymentTransactionStruct->getOrderTransaction()))
            ->setContinueUrl($asyncPaymentTransactionStruct->getReturnUrl());

        return $order;
    }

    /**
     * @param OrderStruct                   $order
     * @param AsyncPaymentTransactionStruct $asyncPaymentTransactionStruct
     * @param SalesChannelContext           $salesChannelContext
     *
     * @return OrderStruct
     */
    private function addBasicOrderData(OrderStruct $order, AsyncPaymentTransactionStruct $asyncPaymentTransactionStruct, SalesChannelContext $salesChannelContext): OrderStruct
    {
        $order->setExtOrderId($asyncPaymentTransactionStruct->getOrder()->getOrderNumber() . '-'. Uuid::randomHex())
            ->setCustomerIp($this->request->getClientIp())
            ->setMerchantPosId(intval(\OpenPayU_Configuration::getOauthClientId() ? \OpenPayU_Configuration::getOauthClientId() : \OpenPayU_Configuration::getMerchantPosId()))
            ->setDescription($this->paymentDetailsReader->generateShortDescription($asyncPaymentTransactionStruct->getOrder()->getOrderNumber()))
            ->setAdditionalDescription($this->paymentDetailsReader->generateLongDescription($asyncPaymentTransactionStruct->getOrder()->getOrderNumber()))
            ->setCurrencyCode($salesChannelContext->getCurrency()->getIsoCode())
            ->setTotalAmount($asyncPaymentTransactionStruct->getOrderTransaction()->getAmount()->getTotalPrice() * 100);

        return $order;
    }

    /**
     * @param OrderStruct                   $order
     * @param AsyncPaymentTransactionStruct $asyncPaymentTransactionStruct
     *
     * @return OrderStruct
     */
    private function addProducts(OrderStruct $order, AsyncPaymentTransactionStruct $asyncPaymentTransactionStruct): OrderStruct
    {
        $products = $asyncPaymentTransactionStruct->getOrder()->getLineItems()->getElements();
        /** @var OrderLineItemEntity $element */
        foreach ($products as $element) {
            if (!$element->getUnitPrice()){
                continue;
            }
            $product = (new Product())
                ->setName($element->getLabel())
                ->setQuantity($element->getQuantity())
                ->setUnitPrice($element->getUnitPrice() * 100)
                ->setVirtual(($element->getType() !== 'product'))
                ->setListingDate($element->getCreatedAt());

            $order->addProduct($product);
        }

        return $order;
    }

    /**
     * @param OrderStruct                   $order
     * @param AsyncPaymentTransactionStruct $asyncPaymentTransactionStruct
     * @param SalesChannelContext           $salesChannelContext
     *
     * @return OrderStruct
     */
    private function addBuyer(OrderStruct $order, AsyncPaymentTransactionStruct $asyncPaymentTransactionStruct, SalesChannelContext $salesChannelContext): OrderStruct
    {
        $customer = $asyncPaymentTransactionStruct->getOrder()->getOrderCustomer();
        try {
            $address = $this->paymentDetailsReader->getOrderAddressEntity($asyncPaymentTransactionStruct->getOrder()->getBillingAddressId());
        } catch (\Exception $exception) {
            $address = null;
        }

        $buyer = new Buyer();
        $buyer->setEmail($customer->getEmail())
            ->setFirstName($customer->getFirstName())
            ->setLastName($customer->getLastName())
            ->setLanguage($this->paymentDetailsReader->getLanguageCode($salesChannelContext));
        if (!empty($address) && !empty($address->getPhoneNumber())) {
            $buyer->setPhone($address->getPhoneNumber());
        }
        $order->setBuyer($buyer);

        return $order;
    }
}
