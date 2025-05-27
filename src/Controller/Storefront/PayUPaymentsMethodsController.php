<?php

namespace Crehler\PayU\Controller\Storefront;

use Crehler\PayU\Checkout\Payment\PayU\SalesChannel\AbstractPaymentMethodsRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class PayUPaymentsMethodsController extends AbstractController
{
    public function __construct(
        private readonly AbstractPaymentMethodsRoute $paymentMethodsRoute
    ) {
    }

    #[Route(
        path: '/payu/payments-methods',
        name: 'frontend.payu.payments-methods',
        defaults: ['XmlHttpRequest' => true],
        methods: ['POST']
    )]
    public function getPaymentMethods(
        Request $request,
        SalesChannelContext $context
    ): Response {
        $lang = $request->get('_locale', 'pl-PL');
        $lang = explode('-', $lang)[0];
        $paymentMethodId = $request->request->get('paymentMethodId');
        return $this->paymentMethodsRoute->getPaymentMethods($paymentMethodId, $lang, $context);
    }
}
