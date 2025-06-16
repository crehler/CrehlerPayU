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
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;

final class Activate extends AbstractLifecycle
{
    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function activate(): void
    {
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPayment::class, true);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPaymentBlikWithoutRedirect::class, true);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPaymentBanks::class, true);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayuPaymentBlik::class, true);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPaymentCards::class, true);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPaymentCredit::class, true);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPaymentWallet::class, true);
        $this->transactionFieldsUtil->createTransactionFields();
        $this->transitionUtil->createPaidToPartiallyPaidTransition();
        $this->customFieldsUtil->createPayuPaymentCustomFields();
        $this->mailUtil->createPaymentLinkMail();
        $this->mailUtil->createSurchargeLinkMail();
    }
}
