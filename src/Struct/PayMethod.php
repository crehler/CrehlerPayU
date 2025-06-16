<?php

declare(strict_types=1);

namespace Crehler\PayU\Struct;

class PayMethod extends PayUStruct
{
    public function __construct(protected string $type, protected string $value)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
