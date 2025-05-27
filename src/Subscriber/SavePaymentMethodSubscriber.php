<?php

namespace Crehler\PayU\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SavePaymentMethodSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityRepository $customerRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced'
        ];
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (is_null($request)) {
            return;
        }
        $order = $event->getOrder();
        $customer = $order->getOrderCustomer();
        if (is_null($customer)) {
            return;
        }

        $payuPaymentMethod = $request->request->get('payuPaymentMethod');
        $this->customerRepository->update([
            [
                'id' => $customer->getCustomerId(),
                'customFields' => [
                    'payuPaymentMethod' => $payuPaymentMethod,
                    'payuPaymentMethodId' => $order->getTransactions()?->first()?->getPaymentMethodId()
                ]
            ]
        ], $event->getContext());
    }
}
