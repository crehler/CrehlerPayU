<?php

namespace Crehler\PayU\Util;

use Crehler\PayU\Service\PaymentLinkService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class CustomFieldsUtil
{
    private const PAYMENT_WL_BL_CUSTOM_FIELD_SET_ID = '01905692394e71bca4496b0440fcf971';
    private const PAYMENT_WL_BL_CUSTOM_FIELD_SET_NAME = 'payu_payment_wl_bl';

    public function __construct(
        private readonly EntityRepository $customFieldSetRepository,
        private readonly Context $context
    ) {
    }

    public function removePayuPaymentCustomFields(): void
    {
        $this->customFieldSetRepository->delete([['id' => self::PAYMENT_WL_BL_CUSTOM_FIELD_SET_ID]], $this->context);
    }

    public function createPayuPaymentCustomFields(): void
    {
        if ($this->isExists()) {
            return;
        }

        $customField = [
            [
                'id' => self::PAYMENT_WL_BL_CUSTOM_FIELD_SET_ID,
                'name' => self::PAYMENT_WL_BL_CUSTOM_FIELD_SET_NAME,
                'config' => [
                    'label' => [
                        'en-GB' => 'PayU payment white and black list',
                        'pl-PL' => 'Biała i czarna lista metod płatności PayU',
                    ],
                ],
                'customFields' => [
                    [
                        'name' => PaymentMethodUtil::PAYMENT_WL_CUSTOM_FIELD_NAME,
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'customFieldPosition' => 0,
                            'customFieldType' => CustomFieldTypes::TEXT,
                            'componentName' => 'sw-field',
                            'label' => [
                                'en-GB' => 'PayU payment white list',
                                'pl-PL' => 'Biała lista metod płatności PayU',
                            ]
                        ],
                    ],
                    [
                        'name' => PaymentMethodUtil::PAYMENT_BL_CUSTOM_FIELD_NAME,
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'customFieldPosition' => 1,
                            'customFieldType' => CustomFieldTypes::TEXT,
                            'componentName' => 'sw-field',
                            'label' => [
                                'en-GB' => 'PayU payment black list',
                                'pl-PL' => 'Czarna lista metod płatności PayU',
                            ]
                        ],
                    ],
                ],
                'relations' => [
                    [
                        'entityName' => 'payment_method',
                    ],
                ],
            ],
        ];
        $this->customFieldSetRepository->create($customField, $this->context);
    }

    private function isExists(): bool
    {
        $criteria = new Criteria([self::PAYMENT_WL_BL_CUSTOM_FIELD_SET_ID]);
        return $this->customFieldSetRepository->searchIds($criteria, $this->context)->firstId() !== null;
    }
}
