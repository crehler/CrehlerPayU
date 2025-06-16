<?php

declare(strict_types=1);

namespace Crehler\PayU\Core\Checkout\Payment;

use Crehler\PayU\Entity\OrderTransactionRepository;
use Crehler\PayU\Service\PayU\ConfigurationService;
use Crehler\PayU\Service\PayU\OrderCreate;
use Crehler\PayU\Service\PayU\UpdateStatus;
use Crehler\PayU\Struct\PayMethod;
use Crehler\PayU\Struct\PayMethods;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class PayUPaymentBlikWithoutRedirect implements SynchronousPaymentHandlerInterface
{
    private const PAY_METHOD_TYPE = 'BLIK_AUTHORIZATION_CODE';

    public function __construct(
        protected readonly ConfigurationService         $configurationService,
        protected readonly LoggerInterface              $logger,
        protected readonly OrderCreate                  $orderCreate,
        protected readonly EntityRepository             $orderTransactionRepository,
        protected readonly OrderTransactionStateHandler $orderTransactionStateHandler,
        protected readonly UpdateStatus                 $updateStatus,
    )
    {
    }

    public function pay(
        SyncPaymentTransactionStruct $transaction,
        RequestDataBag               $dataBag,
        SalesChannelContext          $salesChannelContext
    ): void
    {
        $customer = $salesChannelContext->getCustomer();

        if ($customer === null) {
            throw PaymentException::syncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                CartException::customerNotLoggedIn()->getMessage()
            );
        }

        /** @var \OpenPayU_Result $response */
        $response = \OpenPayU_Order::create(
            $this->orderCreate
                ->createOrder($transaction, $salesChannelContext, true)
                ->setPayMethods($this->buildPayMethods($dataBag->getDigits('blikCode')))
                ->toArray()
        );

        if ($response->getStatus() !== \OpenPayU_Order::SUCCESS) {
            throw PaymentException::syncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                \sprintf(
                    'PayU order was not created successfully (code: %s, message: "%s").',
                    $response->getStatus(),
                    $response->getMessage(),
                )
            );
        }

        $this->orderTransactionRepository->update(
            [
                [
                    'id' => $transaction->getOrderTransaction()->getId(),
                    'customFields' => [
                        OrderTransactionRepository::PAYU_EXTERNAL_ID => $response->getResponse()->orderId,
                        OrderTransactionRepository::CUSTOM_FIELD_IS_SYNC => true,
                    ],
                ]
            ],
            $salesChannelContext->getContext()
        );
    }

    public function notify(
        SyncPaymentTransactionStruct $transaction,
        Request                      $request,
        Context                      $context,
    ): bool
    {
        $salesChannelContextId = $transaction->getOrder()->getSalesChannelId();

        $this->configurationService->initialize(isBlik: true, salesChannel: $salesChannelContextId);

        $result = \OpenPayU_Order::consumeNotification($request->getContent());

        $orderId = $result->getResponse()->order->orderId;
        $shopOrderId = $result->getResponse()->order->extOrderId;
        $paymentStatus = $result->getResponse()->order->status;

        if ($orderId) {
            $order = \OpenPayU_Order::retrieve($orderId);

            if ($order->getStatus() === \OpenPayU_Order::SUCCESS) {
                $this->logger->info('PayU - Paid status: ' . $paymentStatus . ' for order: ' . $shopOrderId);

                match ($paymentStatus) {
                    \OpenPayuOrderStatus::STATUS_COMPLETED => $this->orderTransactionStateHandler->paid(
                        transactionId: $transaction->getOrderTransaction()->getId(),
                        context: $context
                    ),
                    \OpenPayuOrderStatus::STATUS_CANCELED => $this->orderTransactionStateHandler->cancel(
                        transactionId: $transaction->getOrderTransaction()->getId(),
                        context: $context
                    ),
                    \OpenPayuOrderStatus::STATUS_WAITING_FOR_CONFIRMATION => $this->updateStatus->complete($orderId),
                    default => true,
                };

                return true;
            }

            $this->orderTransactionStateHandler->cancel(
                transactionId: $transaction->getOrderTransaction()->getId(),
                context: $context
            );
        }

        return false;
    }

    protected function buildPayMethods(string $blikAuthorizationCode): PayMethods
    {
        return new PayMethods(
            new PayMethod(self::PAY_METHOD_TYPE, $blikAuthorizationCode)
        );
    }
}
