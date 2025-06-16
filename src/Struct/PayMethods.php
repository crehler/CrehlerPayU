<?php

declare(strict_types=1);

namespace Crehler\PayU\Struct;

class PayMethods extends PayUStruct
{
    public function __construct(protected PayMethod $payMethod)
    {
    }

    public function getPayMethod(): PayMethod
    {
        return $this->payMethod;
    }

    public function setPayMethod(PayMethod $payMethod): void
    {
        $this->payMethod = $payMethod;
    }
}
