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

namespace Crehler\PayU\Service\PayU;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Crehler\PayU\Entity\OrderTransactionRepository;
use OpenPayU_Exception;
use OpenPayU_Retrieve;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

use function array_key_exists;
use function array_merge;
use function is_array;
use function json_encode;
use function json_decode;

readonly class TransactionDetails
{
    /**
     * @throws \OpenPayU_Exception_Configuration
     */
    public function __construct(
        ConfigurationService $configurationFactor,
        private EntityRepository $orderTransactionRepository
    ) {
        $configurationFactor->initialize();
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function getData(string $orderID, Context $context): array
    {
        /** @var OrderTransactionCollection $orderTransactions */
        $orderTransactions = $this->orderTransactionRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('orderId', $orderID)),
            $context
        );

        if ($orderTransactions->count() <= 0) {
            return [];
        }

        /** @var OrderTransactionEntity $transaction */
        $transaction = $orderTransactions->first();

        if (
            !is_array($transaction->getCustomFields())
            || !array_key_exists(OrderTransactionRepository::PAYU_EXTERNAL_ID, $transaction->getCustomFields())
        ) {
            return [];
        }

        $payuTransactionID = $transaction->getCustomFields()[OrderTransactionRepository::PAYU_EXTERNAL_ID];

        try {
            $response = \OpenPayU_Order::retrieveTransaction($payuTransactionID);
        } catch (\Exception) {
            return [];
        }

        $transactions = $this->responseToArray($response);

        if (!array_key_exists('transactions', $transactions) || !isset($transactions['transactions'][0])) {
            return [];
        }

        $transaction = $transactions['transactions'][0];

        return $this->getPaymentMethod($transaction['payMethod']['value']);
    }

    private function responseToArray(\OpenPayU_Result $response): array
    {
        return json_decode(json_encode($response->getResponse()), true);
    }

    private function getPaymentMethod(string $payMethodKey): array
    {
        $methods = $this->getAvailableMethods();

        if (array_key_exists($payMethodKey, $methods)) {
            return $methods[$payMethodKey];
        }

        return [];
    }

    private function getAvailableMethods(): array
    {
        $methods = [];

        try {
            $response = OpenPayU_Retrieve::payMethods();

            if ($response->getStatus() === 'SUCCESS') {
                $methods = $this->responseToArray($response);
            }
        } catch (OpenPayU_Exception) {
            return [];
        }

        if (empty($methods)) {
            return [];
        }

        if (!is_array($methods['cardTokens'])) {
            $methods['cardTokens'] = [];
        }

        if (!is_array($methods['pexTokens'])) {
            $methods['pexTokens'] = [];
        }

        if (!is_array($methods['payByLinks'])) {
            $methods['payByLinks'] = [];
        }

        $methods = array_merge($methods['cardTokens'], $methods['pexTokens'], $methods['payByLinks']);
        $return = [];

        foreach ($methods as $method) {
            $key = $method['value'];
            $return[$key] = $method;
        }

        return $return;
    }
}
