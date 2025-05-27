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

namespace Crehler\PayU\Controller\Storefront;

use Crehler\PayU\Core\Checkout\Payment\PayUPayment;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentBlikWithoutRedirect;
use Crehler\PayU\Service\FinalizeTokenGenerator;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use function sprintf;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class PayUNotifyController extends StorefrontController
{
    public function __construct(
        private readonly FinalizeTokenGenerator $finalizeTokenGenerator,
        private readonly LoggerInterface $logger,
        private readonly PayUPayment $payUPayment,
        private readonly PayUPaymentBlikWithoutRedirect $payuPaymentBlikWithoutRedirect
    ) {
    }

    #[Route(
        path: '/crehler/payu/notify',
        name: 'action.crehler.payu.notify',
        options: ['seo' => 'false'],
        defaults: ['csrf_protected' => false],
        methods: ['POST']
    )]
    public function notifyAction(Request $request, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $token = $request->get('_sw_payment_token');

        if (empty($token)) {
            $this->logger->error('A token is required and the notify action.');

            return new JsonResponse(['success' => false], 500);
        }

        $paymentTransactionStruct = $this->finalizeTokenGenerator->getTransationDetails($token, $salesChannelContext);

        try {
            $status = $paymentTransactionStruct instanceof AsyncPaymentTransactionStruct
                ? $this->payUPayment->notify($paymentTransactionStruct, $request, $salesChannelContext)
                : $this->payuPaymentBlikWithoutRedirect->notify(
                    $request,
                    $salesChannelContext,
                    $paymentTransactionStruct
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
