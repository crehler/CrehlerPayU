<?php

namespace Crehler\PayU\Service;

use Crehler\PayU\Core\Checkout\Payment\Event\PaymentLinkSendEvent;
use Crehler\PayU\Core\Checkout\Payment\Event\SurchargeLinkSendEvent;
use Crehler\PayU\Service\PayU\ConfigurationService;
use Crehler\PayU\Service\PayU\OrderCreate;
use OpenPayU_Order;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PaymentLinkService
{
    public const PAYMENT_LINK_TYPE = 'payuPaymentLink';
    public const SURCHARGE_LINK_TYPE = 'payuSurchargeLink';
    public const PREVIOUS_TRANSACTIONS = 'previousTransactions';

    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $orderTransactionRepository,
        private readonly RouterInterface $router,
        private readonly OrderCreate $orderCreate,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private readonly ConfigurationService $configurationService,
        private readonly InitialStateIdLoader $initialStateIdLoader,
        private readonly OrderTransactionStateHandler $orderTransactionStateHandler,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function generatePaymentLink(string $orderId, Context $context): array
    {
        $order = $this->getOrder($orderId, $context);
        if ($order === null) {
            return [
                'success' => false,
                'message' => 'Order not found'
            ];
        }

        $host = $order->getSalesChannel()?->getDomains()?->first()?->getUrl();
        if ($host === null) {
            return [
                'success' => false,
                'message' => 'Host not found'
            ];
        }
        $returnUrl = $host . $this->router->generate('frontend.checkout.finish.page', ['orderId' => $orderId]);

        $transaction = $order->getTransactions()?->last();
        if ($transaction === null) {
            return [
                'success' => false,
                'message' => 'Transaction not found'
            ];
        }

        $salesChannelContext = $this->createSalesChannelContext($order);
        $paymentLink = $this->getPaymentLink($transaction, $order, $salesChannelContext, $returnUrl);

        $this->orderTransactionStateHandler->remind($transaction->getId(), $context);

        $this->saveLinkInTransaction(
            self::PAYMENT_LINK_TYPE,
            $paymentLink,
            $transaction->getId(),
            $salesChannelContext
        );

        $this->eventDispatcher->dispatch(
            new PaymentLinkSendEvent(
                $salesChannelContext->getContext(),
                $order,
                $salesChannelContext->getSalesChannel()->getId()
            )
        );

        return [
            'success' => true,
            'paymentLink' => $paymentLink
        ];
    }

    public function generateSurchargeLink(string $orderId, Context $context): array
    {
        $order = $this->getOrder($orderId, $context);
        if ($order === null) {
            return [
                'success' => false,
                'message' => 'Order not found'
            ];
        }

        $host = $order->getSalesChannel()?->getDomains()?->first()?->getUrl();
        if ($host === null) {
            return [
                'success' => false,
                'message' => 'Host not found'
            ];
        }

        $returnUrl = $host . $this->router->generate('frontend.checkout.finish.page', ['orderId' => $orderId]);
        $surchargeAmount = $this->calculateSurchargeAmount($order);
        if ($surchargeAmount === 0.0) {
            return [
                'success' => false,
                'message' => 'No surcharge needed'
            ];
        }

        $newTransaction = $this->createNewTransaction($surchargeAmount, $order, $context);
        $salesChannelContext = $this->createSalesChannelContext($order);
        $paymentLink = $this->getPaymentLink($newTransaction, $order, $salesChannelContext, $returnUrl);

        $this->saveLinkInTransaction(
            self::SURCHARGE_LINK_TYPE,
            $paymentLink,
            $newTransaction->getId(),
            $salesChannelContext
        );

        $order->getTransactions()->add($newTransaction);

        $this->eventDispatcher->dispatch(
            new SurchargeLinkSendEvent(
                $salesChannelContext->getContext(),
                $order,
                $salesChannelContext->getSalesChannel()->getId()
            )
        );

        return [
            'success' => true,
            'surchargeAmount' => $surchargeAmount,
            'paymentLink' => $paymentLink
        ];
    }

    private function getPaymentLink(
        OrderTransactionEntity $transaction,
        OrderEntity $order,
        SalesChannelContext $salesChannelContext,
        string $returnUrl
    ): string
    {
        $asyncPaymentTransactionToken = new AsyncPaymentTransactionStruct($transaction, $order, $returnUrl);

        $this->configurationService->initialize(salesChannel: $order->getSalesChannelId());
        $payuOrder = $this->orderCreate->createOrder($asyncPaymentTransactionToken, $salesChannelContext);

        /** @var \OpenPayU_Result $response */
        $response = OpenPayU_Order::create($payuOrder->toArray());
        return $response->getResponse()->redirectUri;
    }

    private function createNewTransaction(
        float $amount,
        OrderEntity $order,
        Context $context
    ): OrderTransactionEntity
    {
        $lastOrderTransaction = $order->getTransactions()?->last();
        $previousTransactions = [];
        foreach ($order->getTransactions() as $transaction) {
            if ($transaction->getStateMachineState()?->getTechnicalName() === OrderTransactionStates::STATE_PAID) {
                $previousTransactions[] = $transaction->getId();
            }
        }

        $newOrderTransactionId = Uuid::randomHex();
        $newOrderTransaction = [
            'id' => $newOrderTransactionId,
            'paymentMethodId' => $lastOrderTransaction->getPaymentMethodId(),
            'amount' => $this->createCalculatedPriceForNewTransaction($amount, $lastOrderTransaction),
            'stateId' => $this->initialStateIdLoader->get(OrderTransactionStates::STATE_MACHINE),
            'customFields' => [
                self::PREVIOUS_TRANSACTIONS => array_values($previousTransactions)
            ]
        ];
        $this->orderRepository->upsert([
            [
                'id' => $order->getId(),
                'transactions' => [
                    $newOrderTransaction
                ]
            ]
        ], $context);
        foreach ($previousTransactions as $transactionId) {
            try {
                $this->orderTransactionStateHandler->payPartially($transactionId, $context);
            } catch (IllegalTransitionException) {
//                 already partially paid
            }
        }
        $this->orderTransactionStateHandler->remind($newOrderTransactionId, $context);
        return $this->orderTransactionRepository->search(new Criteria([$newOrderTransactionId]), $context)->first();
    }

    private function createCalculatedPriceForNewTransaction(
        float $surcharge,
        OrderTransactionEntity $lastOrderTransaction
    ): CalculatedPrice {
        return new CalculatedPrice(
            $surcharge,
            $surcharge,
            new CalculatedTaxCollection(),
            $lastOrderTransaction->getAmount()->getTaxRules()
        );
    }

    private function calculateSurchargeAmount(OrderEntity $order): float
    {
        $transactionSum = 0;
        foreach ($order->getTransactions() as $transaction) {
            if ($transaction->getStateMachineState()?->getTechnicalName() === OrderTransactionStates::STATE_PAID){
                $transactionSum += $transaction->getAmount()->getTotalPrice();
            }
        }
        return $order->getAmountTotal() - $transactionSum;
    }

    private function saveLinkInTransaction(
        string $type,
        string $paymentLink,
        string $transactionId,
        SalesChannelContext $salesChannelContext
    ): void
    {
        $this->orderTransactionRepository->update([
            [
                'id' => $transactionId,
                'customFields' => [
                    $type => $paymentLink
                ],
            ]
        ], $salesChannelContext->getContext());
    }

    private function getOrder($orderId, Context $context): ?OrderEntity
    {
        $criteria = (new Criteria([$orderId]))
            ->addAssociation('salesChannel.domains')
            ->addAssociation('lineItems')
            ->addAssociation('transactions.stateMachineState');
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));

        return $this->orderRepository->search($criteria, $context)->first();
    }

    private function createSalesChannelContext(OrderEntity $order): SalesChannelContext
    {
        return $this->salesChannelContextFactory->create(
            Uuid::randomHex(),
            $order->getSalesChannelId(),
            [
                SalesChannelContextService::CUSTOMER_ID => $order->getOrderCustomer()?->getCustomerId(),
                SalesChannelContextService::LANGUAGE_ID => $order->getLanguageId(),
                SalesChannelContextService::CURRENCY_ID => $order->getCurrencyId()
            ]
        );
    }
}
