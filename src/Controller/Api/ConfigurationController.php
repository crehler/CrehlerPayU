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

namespace Crehler\PayU\Controller\Api;

use Crehler\PayU\Core\Checkout\Payment\PayUPayment;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentBlikWithoutRedirect;
use Crehler\PayU\Service\PayU\ConfigurationService;
use Crehler\PayU\Util\PayuMethodFinder;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use function in_array;

#[Route(defaults: ['_routeScope' => ['api']])]
class ConfigurationController extends AbstractController
{
    public function __construct(
        private readonly PayuMethodFinder $methodFinder,
        private readonly ConfigurationService $settingsService
    ) {
    }

    #[Route(
        path: '/api/crehler/payu/sales-channel-payment-configuration-notification',
        name: 'api.crehler.payu.sales-channel-payment-configuration-notification',
        methods: ['POST']
    )]
    public function salesChannelPaymentConfigurationNotification(Request $request, Context $context): JsonResponse
    {
        $paymentMethodIds = $request->get('paymentMethodIds');

        if (
            !in_array(
                $this->methodFinder->getPayUPaymentMethodId(PayUPayment::class, $context),
                $paymentMethodIds,
                true
            )
            || !in_array(
                $this->methodFinder->getPayUPaymentMethodId(PayUPaymentBlikWithoutRedirect::class, $context),
                $paymentMethodIds,
                true
            )
        ) {
            return new JsonResponse(['error' => false]);
        }

        if (!$this->settingsService->isCompleteConfiguration()) {
            return new JsonResponse(['error' => true]);
        }

        return new JsonResponse(
            [
                'error' => false,
                'sandbox' => $this->settingsService->isSandBox(),
                'credentials' => $this->settingsService->checkSavedCredentials($request)
            ]
        );
    }

    #[Route(path: '/api/crehler/payu/check-credentials', name: 'api.crehler.payu.check-credentials', methods: ['POST'])]
    public function checkCredentials(Request $request): JsonResponse
    {
        try {
            $result = $this->settingsService->checkRequestCredentials($request);
        } catch (\Throwable) {
            $result = false;
        }

        return $this->json($result);
    }
}
