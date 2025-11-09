<?php

declare(strict_types=1);

namespace AgusSuroyo\Container\Tests\Fixtures;

class ClassWithUnresolvableParam
{
    public function __construct(
        string $unresolvable
    ) {
    }
}
