<?php

namespace Crehler\PayU\Checkout\Payment\PayU\SalesChannel;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractPaymentMethodsRoute
{
    abstract public function getDecorated(): AbstractPaymentMethodsRoute;
    abstract public function getPaymentMethods(string $paymentMethodId, string $lang, SalesChannelContext $salesChannelContext): Response;
}
