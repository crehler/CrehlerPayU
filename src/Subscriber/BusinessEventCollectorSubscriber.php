<?php

namespace Crehler\PayU\Subscriber;

use Crehler\PayU\Core\Checkout\Payment\Event\PaymentLinkSendEvent;
use Crehler\PayU\Core\Checkout\Payment\Event\SurchargeLinkSendEvent;
use Shopware\Core\Framework\Event\BusinessEventCollector;
use Shopware\Core\Framework\Event\BusinessEventCollectorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BusinessEventCollectorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly BusinessEventCollector $businessEventCollector
    ) {
    }

    public static function getSubscribedEvents()
    {
        return [
            BusinessEventCollectorEvent::NAME => ['addEvents', 1000],
        ];
    }

    public function addEvents(BusinessEventCollectorEvent $event)
    {
        $this->addEventToCollection($event, PaymentLinkSendEvent::class);
        $this->addEventToCollection($event, SurchargeLinkSendEvent::class);
    }

    private function addEventToCollection(BusinessEventCollectorEvent $event, string $eventClass): void
    {
        $collection = $event->getCollection();

        $definition = $this->businessEventCollector->define($eventClass);

        if (!$definition) {
            return;
        }

        $collection->set($definition->getName(), $definition);
    }
}
