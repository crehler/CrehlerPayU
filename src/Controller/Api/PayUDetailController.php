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

namespace Crehler\PayU\Controller\Api;

use Crehler\PayU\Service\PayU\TransactionDetails;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class PayUDetailController extends AbstractController
{
    public function __construct(private readonly TransactionDetails $transactionDetails)
    {
    }

    #[Route(path: '/api/crehler/payu/detail/{id}', name: 'api.action.crehler.payu.detail', methods: ['GET'])]
    public function getDetailInfo(string $id, Context $context): JsonResponse
    {
        $data = $this->transactionDetails->getData($id, $context);

        if (!empty($data)) {
            return new JsonResponse([
                'isPayU' => true,
                'method' => $data,
            ]);
        }

        return new JsonResponse([
            'isPayU' => false,
            'method' => [],
        ]);
    }
}
