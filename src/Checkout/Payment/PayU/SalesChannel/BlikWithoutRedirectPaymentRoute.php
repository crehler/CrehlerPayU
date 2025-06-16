<?php

declare(strict_types=1);

namespace Crehler\PayU\Checkout\Payment\PayU\SalesChannel;

use Crehler\PayU\Entity\OrderTransactionRepository;
use Crehler\PayU\Service\PayU\ConfigurationService;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\AffiliateTracking\AffiliateTrackingListener;
use Shopware\Storefront\Framework\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
class BlikWithoutRedirectPaymentRoute extends AbstractBlikWithoutRedirectPaymentRoute
{
    public function __construct(
        private readonly Router               $router,
        private readonly OrderService         $orderService,
        private readonly PaymentService       $paymentService,
        private readonly EntityRepository     $orderTransactionRepository,
        private readonly ConfigurationService $configurationService,
    )
    {
    }

    public function getDecorated(): AbstractBlikWithoutRedirectPaymentRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(
        path: '/store-api/payu/blik-without-redirect-payment/create-order',
        name: 'store-api.payu.blik-without-redirect-payment.create-order',
        methods: ['POST']
    )]
    public function createOrder(
        RequestDataBag $dataBag,
        Request $request,
        SalesChannelContext $context
    ): BlikWithoutRedirectPaymentTransactionRouteResponse {
        if (!$context->getCustomer()) {
            throw CartException::customerNotLoggedIn();
        }

        $orderId = '';
        $finishUrl = '';

        try {
            $this->addAffiliateTracking($dataBag, $request->getSession());

            $orderId = $this->orderService->createOrder($dataBag, $context);
            $finishUrl = $this->router->generate('frontend.checkout.finish.page', ['orderId' => $orderId]);

            $this->paymentService->handlePaymentByOrder($orderId, $dataBag, $context, $finishUrl);

            return new BlikWithoutRedirectPaymentTransactionRouteResponse(true, $orderId, $finishUrl);
        } catch (\Throwable $e) {
            return new BlikWithoutRedirectPaymentTransactionRouteResponse(
                false,
                $orderId,
                $finishUrl,
                $e->getMessage()
            );
        }
    }

    #[Route(
        path: '/store-api/payu/blik-without-redirect-payment/check-payment-state',
        name: 'store-api.payu.blik-without-redirect-payment.check-payment-state',
        methods: ['POST']
    )]
    public function checkPaymentState(
        Request             $request,
        SalesChannelContext $context
    ): BlikWithoutRedirectPaymentCheckRouteResponse
    {
        $order = $this->getOrderById($request->get('orderId'), $context->getContext());
        $paymentId = $order?->getCustomFieldsValue(OrderTransactionRepository::PAYU_EXTERNAL_ID);

        if ($order === null || $paymentId === null) {
            return new BlikWithoutRedirectPaymentCheckRouteResponse(false, false);
        }

        $this->configurationService->initialize(isBlik: true, salesChannel: $context->getSalesChannelId());
        $paymentStatus = \OpenPayU_Order::retrieve($paymentId)?->getResponse()?->orders[0]?->status;
        return match ($paymentStatus) {
            \OpenPayuOrderStatus::STATUS_COMPLETED => new BlikWithoutRedirectPaymentCheckRouteResponse(
                true,
                false,
                $paymentStatus
            ),
            \OpenPayuOrderStatus::STATUS_CANCELED,
            \OpenPayuOrderStatus::STATUS_REJECTED => new BlikWithoutRedirectPaymentCheckRouteResponse(
                false,
                false,
                $paymentStatus
            ),
            default => new BlikWithoutRedirectPaymentCheckRouteResponse(null, true, $paymentStatus),
        };
    }

    private function addAffiliateTracking(RequestDataBag $dataBag, SessionInterface $session): void
    {
        $affiliateCode = $session->get(AffiliateTrackingListener::AFFILIATE_CODE_KEY);
        $campaignCode = $session->get(AffiliateTrackingListener::CAMPAIGN_CODE_KEY);

        if ($affiliateCode !== null && $campaignCode !== null) {
            $dataBag->set(AffiliateTrackingListener::AFFILIATE_CODE_KEY, $affiliateCode);
            $dataBag->set(AffiliateTrackingListener::CAMPAIGN_CODE_KEY, $campaignCode);
        }
    }

    private function getOrderById(string $orderId, Context $context): ?OrderTransactionEntity
    {
        return $this->orderTransactionRepository->search(
            (new Criteria())
                ->addFilter(new EqualsFilter('orderId', $orderId)),
            $context
        )->first();
    }
}
