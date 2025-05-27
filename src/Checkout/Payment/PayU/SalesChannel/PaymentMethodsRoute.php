<?php

namespace Crehler\PayU\Checkout\Payment\PayU\SalesChannel;

use Crehler\PayU\Core\Content\Event\PayUPaymentMethodsLoadedEvent;
use Crehler\PayU\Service\PayU\ConfigurationService;
use Crehler\PayU\Util\PaymentMethodUtil;
use OpenPayU_Exception_Network;
use OpenPayU_Result;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

class PaymentMethodsRoute extends AbstractPaymentMethodsRoute
{
    public function __construct(
        private readonly ConfigurationService $configurationService,
        private readonly LoggerInterface $logger,
        private readonly EntityRepository $paymentMethodRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getDecorated(): AbstractPaymentMethodsRoute
    {
        throw new DecorationPatternException(self::class);
    }

    public function getPaymentMethods(string $paymentMethodId, string $lang, SalesChannelContext $salesChannelContext): Response
    {
       $this->configurationService->initialize(salesChannel: $salesChannelContext->getSalesChannelId());
       $paymentMethod = $this->getPaymentMethod($paymentMethodId, $salesChannelContext->getContext());
       if (is_null($paymentMethod)) {
           return new Response('Payment method not found', Response::HTTP_NOT_FOUND);
       }
       $paymentMethodsWhiteList = $paymentMethod->getCustomFieldsValue(PaymentMethodUtil::PAYMENT_WL_CUSTOM_FIELD_NAME);
       if ($paymentMethodsWhiteList !== null && $paymentMethodsWhiteList !== "") {
           $paymentMethodsWhiteList = explode(',', $paymentMethodsWhiteList);
       } else {
           $paymentMethodsWhiteList = [];
       }

       $paymentMethodsBlackList = $paymentMethod->getCustomFieldsValue(PaymentMethodUtil::PAYMENT_BL_CUSTOM_FIELD_NAME);
        if ($paymentMethodsBlackList !== null && $paymentMethodsBlackList !== "") {
            $paymentMethodsBlackList = explode(',', $paymentMethodsBlackList);
        } else {
            $paymentMethodsBlackList = [];
        }

       $paymentMethods = $this->getPaymentsMethods($lang);
        if (empty($paymentMethods)) {
            return new Response('Payment methods for lang '. $lang .'not found', Response::HTTP_NOT_FOUND);
        }
        $filteredPaymentMethods = $paymentMethods;

        if (!empty($paymentMethodsWhiteList)){
            $filteredPaymentMethods = array_filter($paymentMethods, fn($paymentMethod) => in_array($paymentMethod->value, $paymentMethodsWhiteList));
        }
        if (!empty($paymentMethodsBlackList)){
            $filteredPaymentMethods = array_filter($paymentMethods, fn($paymentMethod) => !in_array($paymentMethod->value, $paymentMethodsBlackList));
        }

        $event = $this->eventDispatcher->dispatch(
            new PayUPaymentMethodsLoadedEvent(
                $salesChannelContext,
                $paymentMethod,
                $paymentMethods,
                $filteredPaymentMethods
            )
        );

        $filteredPaymentMethods = $event->getFilteredPaymentMethods();

        return new Response(json_encode($filteredPaymentMethods), Response::HTTP_OK);
    }

    private function getPaymentsMethods(string $lang): array
    {
        try {
            $response = \OpenPayU_Retrieve::payMethods($lang)?->getResponse();
            if (!is_null($response)) {
                return $response->payByLinks;
            }
        } catch (OpenPayU_Exception_Network $e) {
            $this->logger->error('Payu payment methods error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getPaymentMethod(string $paymentMethodId, Context $context): ?PaymentMethodEntity
    {
        return $this->paymentMethodRepository->search(new Criteria([$paymentMethodId]), $context)->first();
    }
}
