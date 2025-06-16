<?php

namespace Crehler\PayU\Core\Content\Event;

use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class PayUPaymentMethodsLoadedEvent implements ShopwareEvent
{
    protected SalesChannelContext $salesChannelContext;
    protected PaymentMethodEntity $paymentMethod;
    protected array $allPaymentMethods;
    protected array $filteredPaymentMethods;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        PaymentMethodEntity $paymentMethod,
        array $allPaymentMethods,
        array $filteredPaymentMethods
    ) {
        $this->salesChannelContext = $salesChannelContext;
        $this->paymentMethod = $paymentMethod;
        $this->allPaymentMethods = $allPaymentMethods;
        $this->filteredPaymentMethods = $filteredPaymentMethods;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getPaymentMethod(): PaymentMethodEntity
    {
        return $this->paymentMethod;
    }

    public function getAllPaymentMethods(): array
    {
        return $this->allPaymentMethods;
    }

    public function getFilteredPaymentMethods(): array
    {
        return $this->filteredPaymentMethods;
    }

    public function setFilteredPaymentMethods(array $filteredPaymentMethods): void
    {
        $this->filteredPaymentMethods = $filteredPaymentMethods;
    }

}
