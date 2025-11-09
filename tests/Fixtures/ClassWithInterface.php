<?php

declare(strict_types=1);

namespace AgusSuroyo\Container\Tests\Fixtures;

class ClassWithInterface
{
    public function __construct(
        private SomeInterface $implementation
    ) {
    }

    public function getImplementation(): SomeInterface
    {
        return $this->implementation;
    }
}
