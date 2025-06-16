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

namespace Crehler\PayU\Service;

use Crehler\PayU\Entity\OrderTransactionRepository;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\Token\TokenFactoryInterfaceV2 as TokenFactoryInterface;
use Shopware\Core\Checkout\Payment\Cart\Token\TokenStruct;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Checkout\Payment\Exception\TokenExpiredException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class FinalizeTokenGenerator
{
    public function __construct(
        private EntityRepository      $orderTransactionRepository,
        private RouterInterface       $router,
        private TokenFactoryInterface $tokenFactory
    )
    {
    }

    public function buildUrl(OrderTransactionEntity $orderTransactionEntity): string
    {
        return $this->assembleReturnUrl(
            $this->tokenFactory->generateToken(
                new TokenStruct(
                    null,
                    null,
                    $orderTransactionEntity->getPaymentMethodId(),
                    $orderTransactionEntity->getId(),
                    null,
                    288000,
                    null
                )
            )
        );
    }

    /**
     * @throws PaymentException|InvalidTransactionException|TokenExpiredException
     * @throws InconsistentCriteriaIdsException
     */
    public function getTransactionDetails(
        string  $paymentToken,
        Context $context
    ): AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct
    {
        return $this->getPaymentTransactionStruct(
            orderTransactionId: $this->parseToken($paymentToken)->getTransactionId(),
            context: $context
        );
    }

    private function assembleReturnUrl(string $token): string
    {
        return $this->router->generate(
            'action.crehler.payu.notify',
            ['_sw_payment_token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @throws PaymentException|TokenExpiredException
     */
    private function parseToken(string $token): TokenStruct
    {
        $tokenStruct = $this->tokenFactory->parseToken($token);

        if ($tokenStruct->isExpired()) {
            throw PaymentException::tokenExpired($tokenStruct->getToken());
        }

        // $this->tokenFactory->invalidateToken($tokenStruct->getToken());

        return $tokenStruct;
    }

    /**
     * @throws PaymentException
     * @throws InvalidTransactionException
     * @throws InconsistentCriteriaIdsException
     */
    private function getPaymentTransactionStruct(
        string  $orderTransactionId,
        Context $context
    ): AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct
    {
        $criteria = (new Criteria([$orderTransactionId]))
            ->addAssociation('order')
            ->addAssociation('stateMachineState');

        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if ($orderTransaction === null) {
            throw PaymentException::invalidTransaction($orderTransactionId);
        }

        if ($orderTransaction->getCustomFieldsValue(OrderTransactionRepository::CUSTOM_FIELD_IS_SYNC) === true) {
            return new SyncPaymentTransactionStruct($orderTransaction, $orderTransaction->getOrder());
        }

        return new AsyncPaymentTransactionStruct($orderTransaction, $orderTransaction->getOrder(), '');
    }
}
