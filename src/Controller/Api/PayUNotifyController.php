<?php

declare(strict_types=1);

namespace Crehler\PayU\Controller\Api;

use Crehler\PayU\Core\Checkout\Payment\PayUPayment;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentBlikWithoutRedirect;
use Crehler\PayU\Service\FinalizeTokenGenerator;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
final class PayUNotifyController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface                $logger,
        private readonly FinalizeTokenGenerator         $finalizeTokenGenerator,
        private readonly PayUPayment                    $payUPayment,
        private readonly PayUPaymentBlikWithoutRedirect $payuPaymentBlikWithoutRedirect,
    )
    {
    }

    #[Route(
        path: '/api/_action/payu/notify',
        name: 'action.crehler.payu.notify',
        defaults: ['auth_required' => false],
        methods: ['POST']
    )]
    public function notifyAction(Request $request, Context $context): JsonResponse
    {
        $token = $request->get('_sw_payment_token');

        if (empty($token)) {
            $this->logger->error('A token is required and the notify action.');

            return new JsonResponse(['success' => false], 500);
        }

        $paymentTransactionStruct = $this->finalizeTokenGenerator->getTransactionDetails(
            paymentToken: $token,
            context: $context
        );

        try {
            $status = $paymentTransactionStruct instanceof AsyncPaymentTransactionStruct
                ? $this->payUPayment->notify(transaction: $paymentTransactionStruct, request: $request, context: $context)
                : $this->payuPaymentBlikWithoutRedirect->notify(
                    transaction: $paymentTransactionStruct,
                    request: $request,
                    context: $context
                );
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'Crehler PayU notify exception %s in file "%s" on line "%s" with a message "%s"',
                    $e->getCode(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getMessage()
                )
            );

            return new JsonResponse(['success' => false], 500);
        }

        return new JsonResponse(['success' => $status], ($status ? 200 : 500));
    }
}