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

namespace Crehler\PayU\Entity;

class OrderTransactionRepository
{
    final public const PAYU_EXTERNAL_ID = 'crehler_payu_external_id';

    final public const PAYU_PAY_URL = 'crehler_payu_pay_url';

    public const CUSTOM_FIELD_IS_SYNC = 'isSync';
}
