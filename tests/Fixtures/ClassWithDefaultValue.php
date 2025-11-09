<?php

declare(strict_types=1);

namespace AgusSuroyo\Container\Tests\Fixtures;

class ClassWithDefaultValue
{
    private string $value;

    public function __construct(string $value = 'default')
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
