<?php

declare(strict_types=1);

namespace Crehler\PayU\Checkout\Payment\PayU\SalesChannel;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractBlikWithoutRedirectPaymentRoute
{
    abstract public function getDecorated(): AbstractBlikWithoutRedirectPaymentRoute;

    abstract public function createOrder(
        RequestDataBag $dataBag,
        Request $request,
        SalesChannelContext $context
    ): BlikWithoutRedirectPaymentTransactionRouteResponse;

    abstract public function checkPaymentState(
        Request $request,
        SalesChannelContext $context
    ): BlikWithoutRedirectPaymentCheckRouteResponse;
}
