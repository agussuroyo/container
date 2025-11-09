<?php

declare(strict_types=1);

namespace AgusSuroyo\Container\Tests\Feature;

use AgusSuroyo\Container\Container;
use AgusSuroyo\Container\Tests\Fixtures\SimpleClass;
use AgusSuroyo\Container\Tests\Fixtures\ClassWithDependency;
use AgusSuroyo\Container\Tests\Fixtures\SomeInterface;
use AgusSuroyo\Container\Tests\Fixtures\InterfaceImplementation;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testCanHandleThousandsOfSimpleResolutions(): void
    {
        $iterations = 10000;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $instance = $this->container->get(SimpleClass::class);
            $this->assertInstanceOf(SimpleClass::class, $instance);
        }
        
        $duration = microtime(true) - $startTime;
        
        // Should complete 10k resolutions in under 0.5 seconds
        $this->assertLessThan(0.5, $duration, 
            sprintf('10k simple resolutions took %.4f seconds (expected < 0.5s)', $duration)
        );
        
        echo sprintf("\n✓ 10k simple resolutions: %.4f seconds\n", $duration);
    }

    public function testCanHandleThousandsOfDependencyResolutions(): void
    {
        $iterations = 10000;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $instance = $this->container->get(ClassWithDependency::class);
            $this->assertInstanceOf(ClassWithDependency::class, $instance);
        }
        
        $duration = microtime(true) - $startTime;
        
        // Should complete 10k dependency resolutions in under 1 second
        $this->assertLessThan(1.0, $duration,
            sprintf('10k dependency resolutions took %.4f seconds (expected < 1s)', $duration)
        );
        
        echo sprintf("✓ 10k dependency resolutions: %.4f seconds\n", $duration);
    }

    public function testCanHandleThousandsOfBindingResolutions(): void
    {
        $this->container->bind(SomeInterface::class, InterfaceImplementation::class);
        
        $iterations = 10000;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $instance = $this->container->get(SomeInterface::class);
            $this->assertInstanceOf(InterfaceImplementation::class, $instance);
        }
        
        $duration = microtime(true) - $startTime;
        
        // Should complete 10k binding resolutions in under 0.5 seconds
        $this->assertLessThan(0.5, $duration,
            sprintf('10k binding resolutions took %.4f seconds (expected < 0.5s)', $duration)
        );
        
        echo sprintf("✓ 10k binding resolutions: %.4f seconds\n", $duration);
    }

    public function testSingletonPerformance(): void
    {
        $iterations = 100000;
        $startTime = microtime(true);
        
        // First call creates instance
        $first = $this->container->get(SimpleClass::class);
        
        // Subsequent calls should be extremely fast (just array lookup)
        for ($i = 0; $i < $iterations; $i++) {
            $instance = $this->container->get(SimpleClass::class);
            $this->assertSame($first, $instance);
        }
        
        $duration = microtime(true) - $startTime;
        
        // Should complete 100k singleton lookups in under 0.5 seconds
        $this->assertLessThan(0.5, $duration,
            sprintf('100k singleton lookups took %.4f seconds (expected < 0.5s)', $duration)
        );
        
        echo sprintf("✓ 100k singleton lookups: %.4f seconds\n", $duration);
    }

    public function testMakePerformanceWithoutCaching(): void
    {
        $iterations = 10000;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $instance = $this->container->make(SimpleClass::class);
            $this->assertInstanceOf(SimpleClass::class, $instance);
        }
        
        $duration = microtime(true) - $startTime;
        
        // Make creates new instances each time, so slower than get()
        $this->assertLessThan(2.0, $duration,
            sprintf('10k make() calls took %.4f seconds (expected < 2s)', $duration)
        );
        
        echo sprintf("✓ 10k make() calls: %.4f seconds\n", $duration);
    }

    public function testMemoryEfficiency(): void
    {
        $memoryBefore = memory_get_usage();
        
        // Create 10000 different bindings
        for ($i = 0; $i < 10000; $i++) {
            $this->container->bind("binding_{$i}", SimpleClass::class);
        }
        
        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB
        
        // 10k bindings should use less than 10MB
        $this->assertLessThan(10.0, $memoryUsed,
            sprintf('10k bindings used %.2f MB (expected < 10 MB)', $memoryUsed)
        );
        
        echo sprintf("✓ 10k bindings memory usage: %.2f MB\n", $memoryUsed);
    }

    public function testConcurrentStyleResolution(): void
    {
        // Simulate concurrent-style resolution patterns
        $classes = [
            SimpleClass::class,
            ClassWithDependency::class,
        ];
        
        $iterations = 50000;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $class = $classes[$i % count($classes)];
            $instance = $this->container->get($class);
            $this->assertIsObject($instance);
        }
        
        $duration = microtime(true) - $startTime;
        
        // Should handle 50k mixed resolutions efficiently
        $this->assertLessThan(1.5, $duration,
            sprintf('50k mixed resolutions took %.4f seconds (expected < 1.5s)', $duration)
        );
        
        echo sprintf("✓ 50k mixed resolutions: %.4f seconds\n", $duration);
    }

    public function testMillionBoundChecks(): void
    {
        $this->container->bind(SimpleClass::class, SimpleClass::class);
        
        $iterations = 1000000;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $result = $this->container->bound(SimpleClass::class);
            $this->assertTrue($result);
        }
        
        $duration = microtime(true) - $startTime;
        
        // 1 million bound checks should be fast
        $this->assertLessThan(5.0, $duration,
            sprintf('1M bound checks took %.4f seconds (expected < 5s)', $duration)
        );
        
        echo sprintf("✓ 1M bound checks: %.4f seconds\n", $duration);
    }

    public function testMemoryUsageForSimpleResolutions(): void
    {
        gc_collect_cycles();
        $memoryBefore = memory_get_usage(true);
        
        // Resolve 1000 instances (singletons)
        for ($i = 0; $i < 1000; $i++) {
            $this->container->get(SimpleClass::class);
        }
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024; // Convert to KB
        
        // 1000 singleton resolutions should use minimal memory (< 50KB)
        $this->assertLessThan(50.0, $memoryUsed,
            sprintf('1000 singleton resolutions used %.2f KB (expected < 50 KB)', $memoryUsed)
        );
        
        echo sprintf("✓ 1000 singleton resolutions memory: %.2f KB\n", $memoryUsed);
    }

    public function testMemoryUsageForDependencyResolutions(): void
    {
        gc_collect_cycles();
        $memoryBefore = memory_get_usage(true);
        
        // Resolve 1000 instances with dependencies
        for ($i = 0; $i < 1000; $i++) {
            $this->container->get(ClassWithDependency::class);
        }
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024; // Convert to KB
        
        // Should use minimal memory (< 100KB)
        $this->assertLessThan(100.0, $memoryUsed,
            sprintf('1000 dependency resolutions used %.2f KB (expected < 100 KB)', $memoryUsed)
        );
        
        echo sprintf("✓ 1000 dependency resolutions memory: %.2f KB\n", $memoryUsed);
    }

    public function testMemoryUsageForMakeWithoutCaching(): void
    {
        gc_collect_cycles();
        $memoryBefore = memory_get_usage(true);
        
        // Create 1000 new instances without caching
        for ($i = 0; $i < 1000; $i++) {
            $instance = $this->container->make(SimpleClass::class);
            unset($instance); // Explicit cleanup
        }
        
        gc_collect_cycles();
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024; // Convert to KB
        
        // Memory should be cleaned up properly (< 100KB)
        $this->assertLessThan(100.0, $memoryUsed,
            sprintf('1000 make() calls used %.2f KB after cleanup (expected < 100 KB)', $memoryUsed)
        );
        
        echo sprintf("✓ 1000 make() calls memory after cleanup: %.2f KB\n", $memoryUsed);
    }

    public function testNoMemoryLeaksInRepeatedResolutions(): void
    {
        gc_collect_cycles();
        $memoryStart = memory_get_usage(true);
        
        // First batch
        for ($i = 0; $i < 10000; $i++) {
            $this->container->get(SimpleClass::class);
        }
        
        gc_collect_cycles();
        $memoryFirstBatch = memory_get_usage(true);
        
        // Second batch - should not increase memory significantly
        for ($i = 0; $i < 10000; $i++) {
            $this->container->get(SimpleClass::class);
        }
        
        gc_collect_cycles();
        $memorySecondBatch = memory_get_usage(true);
        
        $leakage = ($memorySecondBatch - $memoryFirstBatch) / 1024; // KB
        
        // Should have minimal memory increase between batches (< 10KB)
        $this->assertLessThan(10.0, $leakage,
            sprintf('Memory increased %.2f KB between batches (expected < 10 KB)', $leakage)
        );
        
        echo sprintf("✓ No memory leaks: %.2f KB increase between batches\n", $leakage);
    }

    public function testContainerInstanceMemoryFootprint(): void
    {
        gc_collect_cycles();
        $memoryBefore = memory_get_usage(true);
        
        // Create 100 container instances
        $containers = [];
        for ($i = 0; $i < 100; $i++) {
            $containers[] = new Container();
        }
        
        $memoryAfter = memory_get_usage(true);
        $memoryPerContainer = ($memoryAfter - $memoryBefore) / 100 / 1024; // KB per container
        
        // Each empty container should be very lightweight (< 5KB)
        $this->assertLessThan(5.0, $memoryPerContainer,
            sprintf('Each container uses %.2f KB (expected < 5 KB)', $memoryPerContainer)
        );
        
        echo sprintf("✓ Container instance footprint: %.2f KB each\n", $memoryPerContainer);
    }
}
