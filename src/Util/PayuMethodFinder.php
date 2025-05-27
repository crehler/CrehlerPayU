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

namespace Crehler\PayU\Util;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

readonly class PayuMethodFinder
{
    public function __construct(private EntityRepository $paymentMethodRepository)
    {
    }

    public function getPayUPaymentMethodId(string $handlerIdentifier, ?Context $context = null): ?string
    {
        return $this->paymentMethodRepository->searchIds(
            (new Criteria())->addFilter(
                new EqualsFilter('handlerIdentifier', $handlerIdentifier)
            ),
            $context ?? Context::createDefaultContext()
        )->firstId();
    }
}
