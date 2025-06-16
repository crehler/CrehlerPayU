<?php

namespace Crehler\PayU\Util;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

readonly class TransitionUtil
{
    private const PAID_TO_PARTIALLY_PAID_TRANSITION_ID = '019053be2fc170be97c15d328917240d';

    public function __construct(
        private EntityRepository $stateMachineRepository,
        private EntityRepository $stateMachineStateRepository,
        private EntityRepository $stateMachineTransitionRepository,
        private Context $context
    )
    {
    }

    public function removePaidToPartiallyPaidTransition(): void
    {
        $this->stateMachineTransitionRepository->delete([['id' => self::PAID_TO_PARTIALLY_PAID_TRANSITION_ID]], $this->context);
    }

    public function createPaidToPartiallyPaidTransition(): void
    {
        if ($this->isTransitionExists()) {
            return;
        }

        $paymentStateMachineId = $this->stateMachineRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('technicalName', 'order_transaction.state')),
            $this->context
        )->first()?->getId();

        $paidStatusId = $this->stateMachineStateRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_PAID))
                ->addFilter(new EqualsFilter('stateMachineId', $paymentStateMachineId)),
            $this->context
        )->first()?->getId();

        $partiallyPaidStatusId = $this->stateMachineStateRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('technicalName', OrderTransactionStates::STATE_PARTIALLY_PAID))
                ->addFilter(new EqualsFilter('stateMachineId', $paymentStateMachineId)),
            $this->context
        )->first()?->getId();

        $this->stateMachineTransitionRepository->create([
            [
                'id' => self::PAID_TO_PARTIALLY_PAID_TRANSITION_ID,
                'actionName' => 'paid_partially',
                'stateMachineId' => $paymentStateMachineId,
                'fromStateId' => $paidStatusId,
                'toStateId' => $partiallyPaidStatusId
            ]
        ], $this->context);
    }

    private function isTransitionExists(): bool
    {
        $transitionId = $this->stateMachineTransitionRepository->searchIds(
            new Criteria([self::PAID_TO_PARTIALLY_PAID_TRANSITION_ID]),
            $this->context
        )->firstId();
        return $transitionId !== null;
    }
}
