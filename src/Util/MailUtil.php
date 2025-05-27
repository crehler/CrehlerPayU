<?php

namespace Crehler\PayU\Util;

use Crehler\PayU\Service\PaymentLinkService;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class MailUtil
{
    private const PAYMENT_LINK_MAIL_TEMPLATE_TYPE_ID = '0190540d4b9f7304be3069283a0e5f3d';
    private const PAYMENT_LINK_MAIL_TEMPLATE_ID = '0190540dbcf27038b530fdafd19ce21c';
    private const PAYMENT_LINK_MAIL_TEMPLATE_TYPE = 'payment_link_mail';
    private const SURCHARGE_LINK_MAIL_TEMPLATE_TYPE_ID = '0190540e300a7084acc9f4402ddd7de8';
    private const SURCHARGE_LINK_MAIL_TEMPLATE_ID = '0190540e4554705c86684bb7223b5569';
    private const SURCHARGE_LINK_MAIL_TEMPLATE_TYPE = 'surcharge_link_mail';

    public function __construct(
        private readonly EntityRepository $mailTemplateRepository,
        private readonly EntityRepository $mailTemplateTypeRepository,
        private readonly Context $context
    ) {
    }

    public function removePaymentLinkMail(): void
    {
        $this->mailTemplateTypeRepository->delete([['id' => self::PAYMENT_LINK_MAIL_TEMPLATE_TYPE_ID]], $this->context);
        $this->mailTemplateRepository->delete([['id' => self::PAYMENT_LINK_MAIL_TEMPLATE_ID]], $this->context);
    }

    public function removeSurchargeLinkMail(): void
    {
        $this->mailTemplateTypeRepository->delete([['id' => self::SURCHARGE_LINK_MAIL_TEMPLATE_TYPE_ID]], $this->context);
        $this->mailTemplateRepository->delete([['id' => self::SURCHARGE_LINK_MAIL_TEMPLATE_ID]], $this->context);
    }
    public function createPaymentLinkMail():void
    {
        if ($this->isMailTemplateTypeExists(self::PAYMENT_LINK_MAIL_TEMPLATE_TYPE_ID)) {
            return;
        }

        $templateData[] = [
            'id' => self::PAYMENT_LINK_MAIL_TEMPLATE_ID,
            'systemDefault' => false,
            'translations' => [
                [
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'subject' => 'Payment Link',
                    'description' => 'Mail with payment link',
                    'senderName' => '{{ salesChannel.name }}',
                    'contentPlain' => '{{ order.transactions.last().customFields.'.PaymentLinkService::PAYMENT_LINK_TYPE.' }}',
                    'contentHtml' => '
                    <div style="font-family:arial; font-size:12px;">
                        <br/>
                        <p>
                        Payment Link for order {{ order.orderNumber }}: <a href="{{ order.transactions.last().customFields.'.PaymentLinkService::PAYMENT_LINK_TYPE.' }}">Pay now</a>
                        </p>
                    </div>',
                ],
            ],
            'mailTemplateType' => [
                'id' => self::PAYMENT_LINK_MAIL_TEMPLATE_TYPE_ID,
                'technicalName' => self::PAYMENT_LINK_MAIL_TEMPLATE_TYPE,
                'availableEntities' => [
                    'order' => 'order'
                ],
                'translations' => [
                    [
                        'languageId' => Defaults::LANGUAGE_SYSTEM,
                        'name' => 'Payment Link',
                    ],
                ],
            ],
        ];

        $this->mailTemplateRepository->create($templateData, $this->context);
    }

    public function createSurchargeLinkMail():void
    {
        if ($this->isMailTemplateTypeExists(self::SURCHARGE_LINK_MAIL_TEMPLATE_TYPE_ID)) {
            return;
        }
        $templateData[] = [
            'id' => self::SURCHARGE_LINK_MAIL_TEMPLATE_ID,
            'systemDefault' => false,
            'translations' => [
                [
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'subject' => 'Surcharge Link',
                    'description' => 'Mail with surcharge link',
                    'senderName' => '{{ salesChannel.name }}',
                    'contentPlain' => '{{ order.transactions.last().customFields.'.PaymentLinkService::SURCHARGE_LINK_TYPE.' }}',
                    'contentHtml' => '
                    <div style="font-family:arial; font-size:12px;">
                        <br/>
                        <p>
                        Surcharge Link for order {{ order.orderNumber }}: <a href="{{ order.transactions.last().customFields.'.PaymentLinkService::SURCHARGE_LINK_TYPE.' }}">Pay now</a>
                        </p>
                    </div>',
                ],
            ],
            'mailTemplateType' => [
                'id' => self::SURCHARGE_LINK_MAIL_TEMPLATE_TYPE_ID,
                'technicalName' => self::SURCHARGE_LINK_MAIL_TEMPLATE_TYPE,
                'availableEntities' => [
                    'order' => 'order'
                ],
                'translations' => [
                    [
                        'languageId' => Defaults::LANGUAGE_SYSTEM,
                        'name' => 'Payment Link',
                    ],
                ],
            ],
        ];

        $this->mailTemplateRepository->create($templateData, $this->context);
    }

    private function isMailTemplateTypeExists(string $mailTemplateId): bool
    {
        $criteria = new Criteria([$mailTemplateId]);
        return $this->mailTemplateTypeRepository->searchIds($criteria, $this->context)->firstId() !== null;
    }
}
