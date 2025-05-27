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

namespace Crehler\PayU\Struct;

use function json_decode;
use function json_encode;

abstract class PayUStruct implements \JsonSerializable
{
    public function jsonSerialize(): array
    {
        $data = [];
        $vars = get_object_vars($this);

        foreach ($vars as $property => $value) {
            if (empty($value)) {
                continue;
            }

            $getter = 'get' . ucfirst($property);

            if (method_exists($this, $getter)) {
                $data[$property] = $this->$getter();
            } else {
                $data[$property] = $value;
            }
        }

        return $data;
    }

    public function toArray(): array
    {
        return json_decode(json_encode($this), true);
    }
}
