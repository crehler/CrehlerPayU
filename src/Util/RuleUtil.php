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
use Shopware\Core\Framework\Rule\Container\AndRule;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\Rule\CurrencyRule;

class RuleUtil
{
    private const RULE_NAME = 'PayU only PLN';

    public function __construct(
        private EntityRepository $ruleRepository,
        private EntityRepository $currencyRepository,
        private readonly Context $context
    ) {
    }

    /**
     * @throws \Exception
     *
     * @return string|null
     */
    public function getRuleId(): ?string
    {
        return $this->checkRuleExist() ?? $this->createRule();
    }

    /**
     * @throws \Exception
     */
    private function checkRuleExist(): ?string
    {
        $ruleIds = $this->ruleRepository->searchIds(
            (new Criteria())->addFilter(new EqualsFilter('name', self::RULE_NAME)),
            $this->context
        );

        if ($ruleIds->getTotal() === 0) {
            return null;
        }

        return $ruleIds->firstId();
    }

    /**
     * @throws \Exception
     */
    private function createRule(): string
    {
        $ruleId = Uuid::randomHex();

        $this->ruleRepository->create(
            [
                [
                    'id' => $ruleId,
                    'name' => self::RULE_NAME,
                    'priority' => 1,
                    'description' => 'The currency required is PLN',
                    'conditions' => [
                        [
                            'type' => (new AndRule())->getName(),
                            'children' => [
                                [
                                    'type' => (new CurrencyRule())->getName(),
                                    'value' => [
                                        'currencyIds' => [$this->getCurrencyID()],
                                        'operator' => CurrencyRule::OPERATOR_EQ,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            $this->context
        );

        return $ruleId;
    }

    /**
     * @throws \Exception
     */
    private function getCurrencyID(): ?string
    {
        $currency = $this->currencyRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('currency.isoCode', 'PLN')),
            $this->context
        );

        if ($currency->count() < 1) {
            throw new \Exception('You must have the currency PLN in the store before installing Polish payments.');
        }

        return $currency->first()?->getId();
    }
}
