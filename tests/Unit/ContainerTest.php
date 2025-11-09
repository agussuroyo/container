<?php

declare(strict_types=1);

namespace AgusSuroyo\Container\Tests\Unit;

use AgusSuroyo\Container\Container;
use AgusSuroyo\Container\Tests\Fixtures\SimpleClass;
use AgusSuroyo\Container\Tests\Fixtures\ClassWithDependency;
use AgusSuroyo\Container\Tests\Fixtures\ClassWithInterface;
use AgusSuroyo\Container\Tests\Fixtures\SomeInterface;
use AgusSuroyo\Container\Tests\Fixtures\InterfaceImplementation;
use AgusSuroyo\Container\Tests\Fixtures\ClassWithDefaultValue;
use AgusSuroyo\Container\Tests\Fixtures\AbstractClass;
use AgusSuroyo\Container\Tests\Fixtures\ClassWithUnresolvableParam;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use RuntimeException;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    // ============ POSITIVE TEST CASES ============
    public function testCanMakeSimpleClass(): void
    {
        $instance = $this->container->make(SimpleClass::class);

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testCanGetSimpleClass(): void
    {
        $instance = $this->container->get(SimpleClass::class);

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testGetReturnsSameInstanceOnMultipleCalls(): void
    {
        $instance1 = $this->container->get(SimpleClass::class);
        $instance2 = $this->container->get(SimpleClass::class);

        $this->assertSame($instance1, $instance2);
    }

    public function testCanResolveClassWithDependencies(): void
    {
        $instance = $this->container->get(ClassWithDependency::class);

        $this->assertInstanceOf(ClassWithDependency::class, $instance);
        $this->assertInstanceOf(SimpleClass::class, $instance->getDependency());
    }

    public function testCanBindInterfaceToImplementation(): void
    {
        $this->container->bind(SomeInterface::class, InterfaceImplementation::class);

        $instance = $this->container->get(SomeInterface::class);

        $this->assertInstanceOf(InterfaceImplementation::class, $instance);
    }

    public function testCanBindWithCallable(): void
    {
        $this->container->bind(SimpleClass::class, function () {
            return new SimpleClass();
        });

        $instance = $this->container->get(SimpleClass::class);

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testSingletonBindingReturnsSameInstance(): void
    {
        $this->container->singleton(SimpleClass::class, SimpleClass::class);

        $instance1 = $this->container->get(SimpleClass::class);
        $instance2 = $this->container->get(SimpleClass::class);

        $this->assertSame($instance1, $instance2);
    }

    public function testCanResolveNestedDependencies(): void
    {
        $this->container->bind(SomeInterface::class, InterfaceImplementation::class);

        $instance = $this->container->get(ClassWithInterface::class);

        $this->assertInstanceOf(ClassWithInterface::class, $instance);
        $this->assertInstanceOf(InterfaceImplementation::class, $instance->getImplementation());
    }

    public function testCanResolveClassWithDefaultValue(): void
    {
        $instance = $this->container->get(ClassWithDefaultValue::class);

        $this->assertInstanceOf(ClassWithDefaultValue::class, $instance);
        $this->assertSame('default', $instance->getValue());
    }

    public function testBoundReturnsTrueForBoundClass(): void
    {
        $this->container->bind(SimpleClass::class, SimpleClass::class);

        $this->assertTrue($this->container->bound(SimpleClass::class));
    }

    public function testBoundReturnsTrueForResolvedClass(): void
    {
        $this->container->get(SimpleClass::class);

        $this->assertTrue($this->container->bound(SimpleClass::class));
    }

    public function testMakeCreatesNewInstanceEachTime(): void
    {
        $instance1 = $this->container->make(SimpleClass::class);
        $instance2 = $this->container->make(SimpleClass::class);

        $this->assertNotSame($instance1, $instance2);
    }

    public function testCanClearSpecificSingleton(): void
    {
        $instance1 = $this->container->get(SimpleClass::class);
        
        $this->container->clearInstance(SimpleClass::class);
        
        $instance2 = $this->container->get(SimpleClass::class);
        
        $this->assertNotSame($instance1, $instance2);
    }

    public function testCanClearAllSingletons(): void
    {
        $simpleInstance = $this->container->get(SimpleClass::class);
        $dependencyInstance = $this->container->get(ClassWithDependency::class);
        
        $this->container->clearInstance();
        
        $newSimpleInstance = $this->container->get(SimpleClass::class);
        $newDependencyInstance = $this->container->get(ClassWithDependency::class);
        
        $this->assertNotSame($simpleInstance, $newSimpleInstance);
        $this->assertNotSame($dependencyInstance, $newDependencyInstance);
    }

    public function testClearInstanceDoesNotAffectBindings(): void
    {
        $this->container->bind(SomeInterface::class, InterfaceImplementation::class);
        $instance1 = $this->container->get(SomeInterface::class);
        
        $this->container->clearInstance(SomeInterface::class);
        
        // Binding should still work
        $instance2 = $this->container->get(SomeInterface::class);
        $this->assertInstanceOf(InterfaceImplementation::class, $instance2);
        $this->assertNotSame($instance1, $instance2);
    }

    public function testClearNonExistentInstanceDoesNotThrow(): void
    {
        // Should not throw an exception
        $this->container->clearInstance('NonExistentClass');
        
        $this->assertTrue(true); // If we get here, test passes
    }

    // ============ NEGATIVE TEST CASES ============
    public function testMakeThrowsExceptionForNonExistentClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class NonExistentClass not found');

        $this->container->make('NonExistentClass');
    }

    public function testMakeThrowsExceptionForAbstractClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not instantiable');

        $this->container->make(AbstractClass::class);
    }

    public function testMakeThrowsExceptionForInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');

        $this->container->make(SomeInterface::class);
    }

    public function testThrowsExceptionForUnresolvableParameter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve');

        $this->container->get(ClassWithUnresolvableParam::class);
    }

    public function testGetThrowsExceptionForNonExistentClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');

        $this->container->get('NonExistentClass');
    }

    public function testBoundReturnsFalseForUnboundClass(): void
    {
        $this->assertFalse($this->container->bound('SomeRandomClass'));
    }

    public function testThrowsExceptionWhenResolvingInterfaceWithoutBinding(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');

        $this->container->get(SomeInterface::class);
    }

    public function testThrowsExceptionForAbstractClassEvenWithGet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not instantiable');

        $this->container->get(AbstractClass::class);
    }
}
