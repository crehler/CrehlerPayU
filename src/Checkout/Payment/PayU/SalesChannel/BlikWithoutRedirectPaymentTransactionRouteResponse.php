<?php

declare(strict_types=1);

namespace Crehler\PayU\Checkout\Payment\PayU\SalesChannel;

use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

class BlikWithoutRedirectPaymentTransactionRouteResponse extends StoreApiResponse
{
    public function __construct(
        bool $success,
        ?string $orderId,
        string $finishUrl,
        ?string $message = null
    ) {
        parent::__construct(
            new ArrayStruct(
                [
                    'success' => $success,
                    'orderId' => $orderId,
                    'finishUrl' => $finishUrl,
                    'message' => $message,
                ]
            )
        );
    }
}
