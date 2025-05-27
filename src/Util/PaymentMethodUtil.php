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

namespace Crehler\PayU\Util;

use Crehler\PayU\Core\Checkout\Payment\PayUPayment;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentBanks;
use Crehler\PayU\Core\Checkout\Payment\PayuPaymentBlik;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentBlikWithoutRedirect;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentCards;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentCredit;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentWallet;
use Crehler\PayU\CrehlerPayU;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;

readonly class PaymentMethodUtil
{
    public const PAYMENT_WL_CUSTOM_FIELD_NAME = 'payu_payment_methods_wl';
    public const PAYMENT_BL_CUSTOM_FIELD_NAME = 'payu_payment_methods_bl';
    private const DATA = [
        PayUPayment::class => [
            'name' => 'PayU',
            'position' => -100,
            'translations' => [
                'de-DE' => [
                    'description' => 'Bezahlung per PayU - einfach, schnell und sicher.',
                ],
                'en-GB' => [
                    'description' => 'Payment via PayU - easy, fast and secure.',
                ],
            ],
            'technicalName' => 'payment_payu'
        ],
        PayUPaymentBlikWithoutRedirect::class => [
            'name' => 'PayU - Blik (without redirect)',
            'position' => -100,
            'active' => true,
            'afterOrderEnabled' => true,
            'technicalName' => 'payment_payu_blik_without_redirect'
        ],
        PayUPaymentBanks::class => [
            'name' => 'PayU - Banks',
            'position' => -100,
            'active' => true,
            'afterOrderEnabled' => true,
            'technicalName' => 'payment_payu_banks',
            'customFields' => [
                self::PAYMENT_BL_CUSTOM_FIELD_NAME => 'blik,c,dpp,dpt,dp,ai,ap,jp'

            ]
        ],
        PayuPaymentBlik::class => [
            'name' => 'PayU - Blik',
            'position' => -100,
            'active' => true,
            'afterOrderEnabled' => true,
            'technicalName' => 'payment_payu_blik',
            'customFields' => [
                self::PAYMENT_WL_CUSTOM_FIELD_NAME => 'blik'
            ]
        ],
        PayUPaymentCards::class => [
            'name' => 'PayU - Cards',
            'position' => -100,
            'active' => true,
            'afterOrderEnabled' => true,
            'technicalName' => 'payment_payu_cards',
            'customFields' => [
                self::PAYMENT_WL_CUSTOM_FIELD_NAME => 'c'
            ]
        ],
        PayUPaymentCredit::class => [
            'name' => 'PayU - Credit',
            'position' => -100,
            'active' => true,
            'afterOrderEnabled' => true,
            'technicalName' => 'payment_payu_credit',
            'customFields' => [
                self::PAYMENT_WL_CUSTOM_FIELD_NAME => 'dpp,dpt,dp,ai'
            ]
        ],
        PayUPaymentWallet::class => [
            'name' => 'PayU - Wallet',
            'position' => -100,
            'active' => true,
            'afterOrderEnabled' => true,
            'technicalName' => 'payment_payu_wallet',
            'customFields' => [
                self::PAYMENT_WL_CUSTOM_FIELD_NAME => 'ap,jp'
            ]
        ]
    ];

    public function __construct(
        private Context          $context,
        private PayuMethodFinder $methodFinder,
        private EntityRepository $paymentRepository,
        private PluginIdProvider $pluginIdProvider,
        private RuleUtil         $ruleUtil,
    )
    {
    }

    public function createPaymentMethod(string $handlerIdentifier): ?string
    {
        $payUPaymentId = $this->methodFinder->getPayUPaymentMethodId($handlerIdentifier, $this->context);

        if ($payUPaymentId) {
            return $payUPaymentId;
        }

        $payuData = self::DATA[$handlerIdentifier] ?? null;

        if ($payuData === null) {
            return null;
        }

        try {
            $ruleId = $this->ruleUtil->getRuleId();
        } catch (\Throwable) {
            $ruleId = null;
        }

        $payUPaymentId = Uuid::randomHex();
        $payuData['handlerIdentifier'] = $handlerIdentifier;
        $payuData['pluginId'] = $this->pluginIdProvider->getPluginIdByBaseClass(CrehlerPayU::class, $this->context);

        if ($handlerIdentifier === PayUPayment::class && $ruleId !== '') {
            $payuData['availabilityRuleId'] = $ruleId;
        }

        $this->paymentRepository->create([$payuData], $this->context);

        return $payUPaymentId;
    }

    public function setPaymentMethodIsActive(string $handlerIdentifier, bool $active): void
    {
        $paymentMethodId = $this->methodFinder->getPayUPaymentMethodId($handlerIdentifier, $this->context);

        if (!$paymentMethodId) {
            return;
        }

        $this->paymentRepository->update([['id' => $paymentMethodId, 'active' => $active]], $this->context);
    }
}
