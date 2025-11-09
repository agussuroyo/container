<?php

declare(strict_types=1);

namespace AgusSuroyo\Container\Tests\Fixtures;

class ClassWithDefaultValue
{
    public function __construct(
        private string $value = 'default'
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
