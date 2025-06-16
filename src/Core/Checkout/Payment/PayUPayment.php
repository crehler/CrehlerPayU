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

namespace Crehler\PayU\Core\Checkout\Payment;

use Crehler\PayU\Service\PaymentLinkService;
use Crehler\PayU\Service\PayU\ConfigurationService;
use Crehler\PayU\Struct\PayMethod;
use Crehler\PayU\Struct\PayMethods;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\System\StateMachine\Exception\StateMachineNotFoundException;
use Shopware\Core\System\StateMachine\Exception\StateMachineStateNotFoundException;
use Crehler\PayU\Entity\OrderTransactionRepository;
use Crehler\PayU\Service\PayU\OrderCreate;
use Crehler\PayU\Service\PayU\UpdateStatus;
use OpenPayU_Exception;
use OpenPayU_Order;
use OpenPayuOrderStatus;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PayUPayment implements AsynchronousPaymentHandlerInterface
{
    private const array NEED_REOPEN_STATUSES = [
        OrderTransactionStates::STATE_REMINDED,
        OrderTransactionStates::STATE_CANCELLED,
        OrderTransactionStates::STATE_PARTIALLY_PAID,
    ];

    private const PAY_METHOD_TYPE = 'PBL';

    public function __construct(
        protected readonly ConfigurationService       $configurationService,
        private readonly LoggerInterface              $logger,
        private readonly OrderCreate                  $orderCreate,
        private readonly EntityRepository             $orderTransactionRepository,
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly UpdateStatus                 $updateStatus,
    )
    {
    }

    /**
     * @throws OpenPayU_Exception
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag                $dataBag,
        SalesChannelContext           $salesChannelContext
    ): RedirectResponse
    {
        $paymentMethod = $dataBag->get('payuPaymentMethod');
        $order = $this->orderCreate->createOrder($transaction, $salesChannelContext);
        if (!is_null($paymentMethod)) {
            $order->setPayMethods($this->buildPayMethods($paymentMethod));
        }

        /** @var \OpenPayU_Result $response */
        $response = OpenPayU_Order::create($order->toArray());
        $data = [
            'id' => $transaction->getOrderTransaction()->getId(),
            'customFields' => [
                OrderTransactionRepository::PAYU_EXTERNAL_ID => $response->getResponse()->orderId,
                PaymentLinkService::PAYMENT_LINK_TYPE => $response->getResponse()->redirectUri
            ],
        ];

        $this->orderTransactionRepository->update([$data], $salesChannelContext->getContext());

        return new RedirectResponse($response->getResponse()->redirectUri);
    }

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request                       $request,
        SalesChannelContext           $salesChannelContext
    ): void
    {
        // Just redirect to Thank you page...
    }

    /**
     * @throws OpenPayU_Exception
     * @throws InconsistentCriteriaIdsException
     * @throws StateMachineNotFoundException
     * @throws StateMachineStateNotFoundException
     */
    public function notify(
        AsyncPaymentTransactionStruct $transaction,
        Request                       $request,
        Context                       $context,
    ): bool
    {
        $salesChannelContextId = $transaction->getOrder()->getSalesChannelId();

        $this->configurationService->initialize(salesChannel: $salesChannelContextId);

        $result = OpenPayU_Order::consumeNotification($request->getContent());

        $orderID = $result->getResponse()->order->orderId;
        $shopOrderId = $result->getResponse()->order->extOrderId;
        // NEW PENDING CANCELED REJECTED COMPLETED WAITING_FOR_CONFIRMATION
        $paymentStatus = $result->getResponse()->order->status;

        if ($orderID) {
            /* Check if OrderId exists in Merchant Service, update Order data by OrderRetrieveRequest */
            $order = OpenPayU_Order::retrieve($orderID);
            if ($order->getStatus() == OpenPayU_Order::SUCCESS) {
                $this->logger->info('PayU - Paid status: ' . $paymentStatus . ' for order: ' . $shopOrderId);

                match ($paymentStatus) {
                    OpenPayuOrderStatus::STATUS_COMPLETED => $this->paidHandler(
                        transaction: $transaction->getOrderTransaction(),
                        context: $context
                    ),
                    OpenPayuOrderStatus::STATUS_CANCELED => $this->transactionStateHandler->cancel(
                        transactionId: $transaction->getOrderTransaction()->getId(),
                        context: $context
                    ),
                    OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION => $this->updateStatus->complete($orderID),
                    default => true,
                };

                return true;
            }

            $this->transactionStateHandler->cancel(
                transactionId: $transaction->getOrderTransaction()->getId(),
                context: $context
            );

            return false;
        }

        return false;
    }

    protected function paidHandler(OrderTransactionEntity $transaction, Context $context): void
    {
        if (in_array($transaction->getStateMachineState()?->getTechnicalName(), self::NEED_REOPEN_STATUSES, true)) {
            $this->transactionStateHandler->reopen(
                transactionId: $transaction->getId(),
                context: $context
            );

            $previousTransactions = $transaction->getCustomFieldsValue(PaymentLinkService::PREVIOUS_TRANSACTIONS);
            if ($previousTransactions !== null) {

                foreach ($previousTransactions as $previousTransaction) {
                    $this->transactionStateHandler->reopen(
                        transactionId: $previousTransaction,
                        context: $context
                    );
                }
            }
        }

        $this->transactionStateHandler->paid(
            transactionId: $transaction->getId(),
            context: $context
        );

        $previousTransactions = $transaction->getCustomFieldsValue(PaymentLinkService::PREVIOUS_TRANSACTIONS);
        if ($previousTransactions !== null) {

            foreach ($previousTransactions as $previousTransaction) {
                $this->transactionStateHandler->paid(
                    transactionId: $previousTransaction,
                    context: $context
                );
            }
        }
    }

    protected function buildPayMethods(string $paymentMethod): PayMethods
    {
        return new PayMethods(
            new PayMethod(self::PAY_METHOD_TYPE, $paymentMethod)
        );
    }
}
