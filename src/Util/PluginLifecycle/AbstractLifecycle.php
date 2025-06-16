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

namespace Crehler\PayU\Util\PluginLifecycle;

use Crehler\PayU\Util\CustomFieldsUtil;
use Crehler\PayU\Util\MailUtil;
use Crehler\PayU\Util\TransitionUtil;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Crehler\PayU\Struct\DefaultSettings;
use Crehler\PayU\Util\PaymentMethodUtil;
use Crehler\PayU\Util\PayuMethodFinder;
use Crehler\PayU\Util\RuleUtil;
use Crehler\PayU\Util\TransactionFieldsUtil;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractLifecycle
{
    protected InstallContext $lifecycleContext;
    protected PaymentMethodUtil $paymentMethodUtil;
    protected EntityRepository|null $systemConfigRepository;
    protected SystemConfigService|null $systemConfigService;
    protected TransactionFieldsUtil $transactionFieldsUtil;
    protected TransitionUtil $transitionUtil;
    protected MailUtil $mailUtil;
    protected CustomFieldsUtil $customFieldsUtil;

    public function __construct(ContainerInterface $container, InstallContext $lifecycleContext)
    {
        /** @var EntityRepository $currencyRepository */
        $currencyRepository = $container->get('currency.repository');
        /** @var EntityRepository $customFieldRepository */
        $customFieldRepository = $container->get('custom_field.repository');
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $container->get('payment_method.repository');
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $container->get(PluginIdProvider::class);
        /** @var EntityRepository $ruleRepository */
        $ruleRepository = $container->get('rule.repository');
        /** @var EntityRepository $ruleRepository */
        $stateMachineRepository = $container->get('state_machine.repository');
        /** @var EntityRepository $ruleRepository */
        $stateMachineStateRepository = $container->get('state_machine_state.repository');
        /** @var EntityRepository $ruleRepository */
        $stateMachineTransitionRepository = $container->get('state_machine_transition.repository');
        /** @var EntityRepository $ruleRepository */
        $mailTemplateRepository = $container->get('mail_template.repository');
        /** @var EntityRepository $ruleRepository */
        $mailTemplateTypeRepository = $container->get('mail_template_type.repository');
        /** @var EntityRepository $ruleRepository */
        $customFieldRepository = $container->get('custom_field_set.repository');
        $ruleUtil = new RuleUtil(
            $ruleRepository,
            $currencyRepository,
            $lifecycleContext->getContext()
        );
        $methodFinder = new PayuMethodFinder($paymentRepository);
        $this->lifecycleContext = $lifecycleContext;
        $this->paymentMethodUtil = new PaymentMethodUtil(
            $lifecycleContext->getContext(),
            $methodFinder,
            $paymentRepository,
            $pluginIdProvider,
            $ruleUtil
        );
        $this->systemConfigRepository = $container->get('system_config.repository');
        $this->systemConfigService = $container->get(SystemConfigService::class);
        $this->transactionFieldsUtil = new TransactionFieldsUtil(
            $customFieldRepository,
            $lifecycleContext->getContext()
        );
        $this->transitionUtil = new TransitionUtil(
            $stateMachineRepository,
            $stateMachineStateRepository,
            $stateMachineTransitionRepository,
            $lifecycleContext->getContext()
        );
        $this->mailUtil = new MailUtil(
            $mailTemplateRepository,
            $mailTemplateTypeRepository,
            $lifecycleContext->getContext()
        );
        $this->customFieldsUtil = new CustomFieldsUtil(
            $customFieldRepository,
            $lifecycleContext->getContext()
        );
    }

    protected function addDefaultConfiguration(): void
    {
        $data = [];

        foreach ((new DefaultSettings())->jsonSerialize() as $key => $value) {
            if ($value === null || $value === []) {
                continue;
            }

            $key = 'CrehlerPayU.config.' . $key;
            $data[] = [
                'id' => Uuid::randomHex(),
                'configurationKey' => $key,
                'configurationValue' => $value,
            ];
        }

        $this->systemConfigRepository->upsert($data, Context::createDefaultContext());
    }
}
