<?php

declare(strict_types=1);

namespace Crehler\PayU\Controller\Storefront;

use Crehler\PayU\Checkout\Payment\PayU\SalesChannel\AbstractBlikWithoutRedirectPaymentRoute;
use Crehler\PayU\Checkout\Payment\PayU\SalesChannel\BlikWithoutRedirectPaymentCheckRouteResponse;
use Crehler\PayU\Checkout\Payment\PayU\SalesChannel\BlikWithoutRedirectPaymentTransactionRouteResponse;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class PayUPaymentBlikWithoutRedirectController extends AbstractController
{
    public function __construct(
        private readonly AbstractBlikWithoutRedirectPaymentRoute $blikWithoutRedirectPaymentRoute
    ) {
    }

    #[Route(
        path: '/payu/blik-without-redirect-payment/create-order',
        name: 'payu.blik-without-redirect-payment.create-order',
        defaults: ['XmlHttpRequest' => true],
        methods: ['POST']
    )]
    public function createOrder(
        RequestDataBag $dataBag,
        Request $request,
        SalesChannelContext $context
    ): BlikWithoutRedirectPaymentTransactionRouteResponse {
        return $this->blikWithoutRedirectPaymentRoute->createOrder($dataBag, $request, $context);
    }

    #[Route(
        path: '/payu/blik-without-redirect-payment/check-payment-state',
        name: 'payu.blik-without-redirect-payment.check-payment-state',
        defaults: ['XmlHttpRequest' => true],
        methods: ['POST']
    )]
    public function checkPaymentState(
        Request $request,
        SalesChannelContext $context
    ): BlikWithoutRedirectPaymentCheckRouteResponse {
        return $this->blikWithoutRedirectPaymentRoute->checkPaymentState($request, $context);
    }
}
