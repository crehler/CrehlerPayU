<?php

declare(strict_types=1);

namespace Crehler\PayU\Checkout\Payment\PayU\SalesChannel;

use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

class BlikWithoutRedirectPaymentCheckRouteResponse extends StoreApiResponse
{
    public function __construct(?bool $success, bool $waiting, ?string $status = null)
    {
        parent::__construct(
            new ArrayStruct(['success' => $success, 'waiting' => $waiting, 'status' => $status])
        );
    }
}
