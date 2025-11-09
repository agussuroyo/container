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

    // ============ STRING/WORD KEY TESTS ============
    public function testCanBindWithStringKey(): void
    {
        $this->container->bind('my.service', SimpleClass::class);

        $instance = $this->container->get('my.service');

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testCanBindWithDottedNotation(): void
    {
        $this->container->bind('app.logger.file', SimpleClass::class);

        $instance = $this->container->get('app.logger.file');

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testCanBindMultipleStringKeysToSameClass(): void
    {
        $this->container->bind('logger.main', SimpleClass::class);
        $this->container->bind('logger.backup', SimpleClass::class);

        $instance1 = $this->container->get('logger.main');
        $instance2 = $this->container->get('logger.backup');

        $this->assertInstanceOf(SimpleClass::class, $instance1);
        $this->assertInstanceOf(SimpleClass::class, $instance2);
        // Different instances because different keys
        $this->assertNotSame($instance1, $instance2);
    }

    public function testCanBindStringKeyWithCallable(): void
    {
        $this->container->bind('custom.service', function () {
            $instance = new SimpleClass();
            return $instance;
        });

        $instance = $this->container->get('custom.service');

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testStringKeyBindingReturnsSameInstanceOnMultipleCalls(): void
    {
        $this->container->bind('shared.service', SimpleClass::class);

        $instance1 = $this->container->get('shared.service');
        $instance2 = $this->container->get('shared.service');

        $this->assertSame($instance1, $instance2);
    }

    public function testCanUseSingletonWithStringKey(): void
    {
        $this->container->singleton('singleton.service', SimpleClass::class);

        $instance1 = $this->container->get('singleton.service');
        $instance2 = $this->container->get('singleton.service');

        $this->assertSame($instance1, $instance2);
    }

    public function testBoundReturnsTrueForStringKey(): void
    {
        $this->container->bind('my.custom.key', SimpleClass::class);

        $this->assertTrue($this->container->bound('my.custom.key'));
    }

    public function testBoundReturnsFalseForUnboundStringKey(): void
    {
        $this->assertFalse($this->container->bound('non.existent.key'));
    }

    public function testCanClearSingletonWithStringKey(): void
    {
        $this->container->bind('clearable.service', SimpleClass::class);
        
        $instance1 = $this->container->get('clearable.service');
        $this->container->clearInstance('clearable.service');
        $instance2 = $this->container->get('clearable.service');

        $this->assertNotSame($instance1, $instance2);
    }

    public function testCanBindStringKeyWithComplexDependencies(): void
    {
        $this->container->bind('complex.service', ClassWithDependency::class);

        $instance = $this->container->get('complex.service');

        $this->assertInstanceOf(ClassWithDependency::class, $instance);
        $this->assertInstanceOf(SimpleClass::class, $instance->getDependency());
    }

    public function testCanBindStringKeyWithCallableReturningComplexObject(): void
    {
        $this->container->bind(SomeInterface::class, InterfaceImplementation::class);
        
        $this->container->bind('factory.service', function () {
            return new ClassWithDependency(new SimpleClass());
        });

        $instance = $this->container->get('factory.service');

        $this->assertInstanceOf(ClassWithDependency::class, $instance);
    }

    public function testStringKeyBindingsAreIndependentFromClassBindings(): void
    {
        // Bind the class directly
        $classInstance = $this->container->get(SimpleClass::class);
        
        // Bind with a string key
        $this->container->bind('named.service', SimpleClass::class);
        $namedInstance = $this->container->get('named.service');

        // Should be different instances
        $this->assertNotSame($classInstance, $namedInstance);
    }

    public function testCanUseNumericStringAsKey(): void
    {
        $this->container->bind('123', SimpleClass::class);

        $instance = $this->container->get('123');

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testCanUseUnderscoreInStringKey(): void
    {
        $this->container->bind('my_custom_service', SimpleClass::class);

        $instance = $this->container->get('my_custom_service');

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testCanUseDashInStringKey(): void
    {
        $this->container->bind('my-custom-service', SimpleClass::class);

        $instance = $this->container->get('my-custom-service');

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testStringKeyWithColonNotation(): void
    {
        $this->container->bind('service:logger', SimpleClass::class);

        $instance = $this->container->get('service:logger');

        $this->assertInstanceOf(SimpleClass::class, $instance);
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
