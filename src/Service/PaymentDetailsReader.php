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

namespace Crehler\PayU\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Crehler\PayU\Service\PayU\ConfigurationService;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

use function explode;
use function in_array;
use function str_contains;
use function str_replace;

readonly class PaymentDetailsReader implements PaymentDetailsReaderInterface
{
    public function __construct(
        private SystemConfigService $configurationService,
        private EntityRepository $countryRepository,
        private EntityRepository $languageRepository,
        private EntityRepository $localeRepository,
        private EntityRepository $orderAddressRepository
    ) {
    }

    public function getLanguageCode(SalesChannelContext $salesChannelContext): string
    {
        $accepted = ['pl', 'en', 'de'];
        try {
            $languageEntity = $this->getLanguageEntity($salesChannelContext->getSalesChannel()->getLanguageId());
            $localeEntity = $this->getLocaleEntity($languageEntity->getLocaleId());
        } catch (\Exception) {
            return 'en';
        }

        if (str_contains($localeEntity->getCode(), '-')) {
            $code = explode('-', $localeEntity->getCode());
        } else {
            $code = explode('_', $localeEntity->getCode());
        }

        return in_array($code[0], $accepted) ? $code[0] : 'en';
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrderAddressEntity(string $orderAddressID): OrderAddressEntity
    {
        return $this->orderAddressRepository
            ->search(new Criteria([$orderAddressID]), Context::createDefaultContext())
            ->getEntities()
            ->first();
    }

    public function getCountryCode(string $countryID): string
    {
        try {
            $countryEntity = $this->getCountryEntity($countryID);
        } catch (\Exception) {
            return '';
        }

        return $countryEntity->getIso();
    }

    public function generateShortDescription(string $orderNumber): string
    {
        return str_replace(
            '{number}',
            $orderNumber,
            $this->configurationService->get(
                ConfigurationService::CONFIG_PLUGIN_PREFIX . ConfigurationService::CONFIG_ORDER_DESCRIPTION_SHORT
            )
        );
    }

    public function generateLongDescription(string $orderNumber): string
    {
        return str_replace(
            '{number}',
            $orderNumber,
            $this->configurationService->get(
                ConfigurationService::CONFIG_PLUGIN_PREFIX . ConfigurationService::CONFIG_ORDER_DESCRIPTION_LONG
            )
        );
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getLanguageEntity(string $languageID): LanguageEntity
    {
        return $this->languageRepository
            ->search(new Criteria([$languageID]), Context::createDefaultContext())
            ->getEntities()
            ->first();
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getLocaleEntity(string $localeID): LocaleEntity
    {
        return $this->localeRepository
            ->search(new Criteria([$localeID]), Context::createDefaultContext())
            ->getEntities()
            ->first();
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getCountryEntity(string $countryID): CountryEntity
    {
        return $this->countryRepository
            ->search(new Criteria([$countryID]), Context::createDefaultContext())
            ->getEntities()
            ->first();
    }
}
