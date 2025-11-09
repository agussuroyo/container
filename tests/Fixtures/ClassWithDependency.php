<?php

declare(strict_types=1);

namespace AgusSuroyo\Container\Tests\Fixtures;

class ClassWithDependency
{
    private SimpleClass $dependency;

    public function __construct(SimpleClass $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getDependency(): SimpleClass
    {
        return $this->dependency;
    }
}
