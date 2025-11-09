<?php

declare(strict_types=1);

namespace AgusSuroyo\Container\Tests\Fixtures;

class ClassWithInterface
{
    private SomeInterface $implementation;

    public function __construct(SomeInterface $implementation)
    {
        $this->implementation = $implementation;
    }

    public function getImplementation(): SomeInterface
    {
        return $this->implementation;
    }
}
