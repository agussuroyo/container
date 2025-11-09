<?php

declare(strict_types=1);

namespace AgusSuroyo\Container\Tests\Fixtures;

class InterfaceImplementation implements SomeInterface
{
    public function doSomething(): string
    {
        return 'implementation';
    }
}
