<?php

namespace Crehler\PayU\Controller\Api;

use Crehler\PayU\Service\PaymentLinkService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class PayUPaymentLinkController extends AbstractController
{
    public function __construct(
        private readonly PaymentLinkService $paymentLinkService
    ) {
    }

    #[Route(
        path: '/api/crehler/payu/payment-link',
        name: 'api.crehler.payu.payment-link',
        methods: ['POST']
    )]
    public function sendPaymentLink(Request $request, Context $context): JsonResponse
    {
        $orderId = $request->get('orderId');
        return new JsonResponse($this->paymentLinkService->generatePaymentLink($orderId, $context));
    }

    #[Route(
        path: '/api/crehler/payu/surcharge-link',
        name: 'api.crehler.payu.surcharge-link',
        methods: ['POST']
    )]
    public function sendSurchargeLink(Request $request, Context $context): JsonResponse
    {
        $orderId = $request->get('orderId');
        return new JsonResponse($this->paymentLinkService->generateSurchargeLink($orderId, $context));
    }
}
