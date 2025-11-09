<?php

declare(strict_types=1);

namespace AgusSuroyo\Container\Tests\Fixtures;

class ClassWithDependency
{
    public function __construct(
        private SimpleClass $dependency
    ) {
    }

    public function getDependency(): SimpleClass
    {
        return $this->dependency;
    }
}
