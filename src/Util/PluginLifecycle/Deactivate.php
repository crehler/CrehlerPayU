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

final class Deactivate extends AbstractLifecycle
{
    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function deactivate(): void
    {
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPayment::class, false);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPaymentBlikWithoutRedirect::class, false);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPaymentBanks::class, false);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayuPaymentBlik::class, false);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPaymentCards::class, false);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPaymentCredit::class, false);
        $this->paymentMethodUtil->setPaymentMethodIsActive(PayUPaymentWallet::class, false);
        $this->transactionFieldsUtil->removeTransactionFields();
        $this->transitionUtil->removePaidToPartiallyPaidTransition();
        $this->mailUtil->removeSurchargeLinkMail();
        $this->mailUtil->removePaymentLinkMail();
        $this->customFieldsUtil->removePayuPaymentCustomFields();
    }
}
