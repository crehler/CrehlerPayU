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

namespace Crehler\PayU\Util\PluginLifecycle;

use Crehler\PayU\Core\Checkout\Payment\PayUPayment;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentBanks;
use Crehler\PayU\Core\Checkout\Payment\PayuPaymentBlik;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentBlikWithoutRedirect;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentCards;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentCredit;
use Crehler\PayU\Core\Checkout\Payment\PayUPaymentWallet;
use Crehler\PayU\Service\PayU\ConfigurationService;

final class Install extends AbstractLifecycle
{
    public function install(): void
    {
        $payuPaymentId = $this->paymentMethodUtil->createPaymentMethod(PayUPayment::class);
        $this->paymentMethodUtil->createPaymentMethod(PayUPaymentBlikWithoutRedirect::class);
        $this->paymentMethodUtil->createPaymentMethod(PayUPaymentBanks::class);
        $this->paymentMethodUtil->createPaymentMethod(PayuPaymentBlik::class);
        $this->paymentMethodUtil->createPaymentMethod(PayUPaymentCards::class);
        $this->paymentMethodUtil->createPaymentMethod(PayUPaymentCredit::class);
        $this->paymentMethodUtil->createPaymentMethod(PayUPaymentWallet::class);
        $this->transitionUtil->createPaidToPartiallyPaidTransition();
        $this->mailUtil->createPaymentLinkMail();
        $this->mailUtil->createSurchargeLinkMail();
        $this->customFieldsUtil->createPayuPaymentCustomFields();

        $this->savePaymentMethodId($payuPaymentId);
        $this->addDefaultConfiguration();
    }

    private function savePaymentMethodId(string $paymentMethodId): void
    {
        $this->systemConfigService->set(
            ConfigurationService::CONFIG_PLUGIN_PREFIX . ConfigurationService::CONFIG_PAYMENT_METHOD_ID,
            $paymentMethodId
        );
    }
}
