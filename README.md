# Container

A simple dependency injection container for PHP 8.1+.

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Overview

This is a lightweight dependency injection (DI) container designed for performance and memory efficiency. It provides automatic dependency resolution, interface binding, and singleton management with minimal overhead.

### Key Features

- **Fast**: Handles 10,000+ resolutions per second
- **Memory Efficient**: Minimal memory footprint (~5KB per container instance)
- **Auto-wiring**: Automatically resolves constructor dependencies
- **Interface Binding**: Bind interfaces to concrete implementations
- **Singleton Support**: Manage singleton instances with automatic caching and clearing
- **Zero Configuration**: Works out of the box with no setup
- **Type-Safe**: Full PHP 8.1+ type hints and generics support
- **Thoroughly Tested**: Comprehensive unit and performance tests

## Installation

Install via Composer:

```bash
composer require agussuroyo/container
```

## Requirements

- PHP 8.1 or higher
- No additional dependencies

## Quick Start

```php
<?php

use AgusSuroyo\Container\Container;

// Create a container instance
$container = new Container();

// Resolve a simple class
$instance = $container->get(MyClass::class);

// The container automatically resolves dependencies
$service = $container->get(MyService::class);
```

## Usage

### Basic Resolution

The container can automatically resolve classes with no dependencies or with resolvable dependencies:

```php
class SimpleService
{
    public function doSomething(): void
    {
        echo "Doing something!";
    }
}

$service = $container->get(SimpleService::class);
$service->doSomething();
```

### Automatic Dependency Injection

The container automatically resolves constructor dependencies:

```php
class Database
{
    public function query(string $sql): array
    {
        // Execute query
        return [];
    }
}

class UserRepository
{
    public function __construct(
        private Database $database
    ) {}
    
    public function findAll(): array
    {
        return $this->database->query('SELECT * FROM users');
    }
}

// Container automatically injects Database into UserRepository
$repository = $container->get(UserRepository::class);
```

### Interface Binding

Bind interfaces to concrete implementations:

```php
interface LoggerInterface
{
    public function log(string $message): void;
}

class FileLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        file_put_contents('app.log', $message . PHP_EOL, FILE_APPEND);
    }
}

// Bind interface to implementation
$container->bind(LoggerInterface::class, FileLogger::class);

// Now you can resolve the interface
$logger = $container->get(LoggerInterface::class);
$logger->log('Application started');
```

### Binding with Closures

Use closures for custom instantiation logic:

```php
$container->bind(Database::class, function () {
    return new Database(
        host: 'localhost',
        username: 'root',
        password: 'secret'
    );
});

$db = $container->get(Database::class);
```

### Singleton Management

The `get()` method automatically returns the same instance on subsequent calls:

```php
$instance1 = $container->get(MyService::class);
$instance2 = $container->get(MyService::class);

// Both variables reference the same instance
assert($instance1 === $instance2);
```

You can also explicitly declare singletons:

```php
$container->singleton(Cache::class, RedisCache::class);
```

### Creating New Instances

Use `make()` to create a new instance each time (bypasses singleton cache):

```php
$instance1 = $container->make(MyClass::class);
$instance2 = $container->make(MyClass::class);

// Different instances
assert($instance1 !== $instance2);
```

### Checking Bindings

Check if a class or interface is bound:

```php
if ($container->bound(LoggerInterface::class)) {
    $logger = $container->get(LoggerInterface::class);
}
```

### Clearing Singletons

Clear a specific singleton instance or all singleton instances:

```php
// Clear a specific singleton
$instance1 = $container->get(MyService::class);
$container->clearInstance(MyService::class);
$instance2 = $container->get(MyService::class);
// $instance1 !== $instance2 (new instance created)

// Clear all singletons
$container->clearInstance();
```

This is useful for testing scenarios or when you need to reset the container state without creating a new container instance.

### Default Parameter Values

The container respects default parameter values:

```php
class ConfigService
{
    public function __construct(
        private string $environment = 'production'
    ) {}
    
    public function getEnv(): string
    {
        return $this->environment;
    }
}

// Uses the default value 'production'
$config = $container->get(ConfigService::class);
echo $config->getEnv(); // Outputs: production
```

## Advanced Usage

### Nested Dependencies

The container recursively resolves nested dependencies:

```php
class Logger { }

class Database
{
    public function __construct(private Logger $logger) {}
}

class UserRepository
{
    public function __construct(private Database $database) {}
}

class UserService
{
    public function __construct(private UserRepository $repository) {}
}

// Automatically resolves: UserService -> UserRepository -> Database -> Logger
$service = $container->get(UserService::class);
```

### Complex Binding Scenarios

```php
// Bind with factory pattern
$container->bind(Connection::class, function () use ($config) {
    return match($config['driver']) {
        'mysql' => new MySQLConnection($config['mysql']),
        'pgsql' => new PostgresConnection($config['pgsql']),
        default => throw new Exception('Unknown driver')
    };
});

// Bind multiple implementations
$container->bind('logger.file', FileLogger::class);
$container->bind('logger.email', EmailLogger::class);
```

## API Reference

### `bind(string $abstract, callable|string $concrete): void`

Bind an abstract type (interface or class name) to a concrete implementation.

- `$abstract`: Interface or class name
- `$concrete`: Class name (string) or factory closure

### `singleton(string $abstract, callable|string $concrete): void`

Bind a singleton (same as `bind()`, included for semantic clarity).

### `get(string $abstract): object`

Resolve and return an instance. Subsequent calls return the same instance (singleton behavior).

- Returns: Instance of the requested type
- Throws: `InvalidArgumentException` if class doesn't exist
- Throws: `RuntimeException` if class is not instantiable

### `make(string $class): object`

Create a new instance each time, bypassing the singleton cache.

- Returns: New instance of the requested type
- Throws: `InvalidArgumentException` if class doesn't exist
- Throws: `RuntimeException` if class is not instantiable

### `bound(string $abstract): bool`

Check if an abstract type has been bound or resolved.

- Returns: `true` if bound, `false` otherwise

### `clearInstance(?string $abstract = null): void`

Clear a specific singleton instance or all singleton instances.

- `$abstract`: The abstract to clear, or `null` to clear all instances
- Note: This does not remove bindings, only clears cached instances

## Performance

This container is optimized for performance and memory efficiency:

### Speed Benchmarks

- **10,000 simple resolutions**: < 0.5 seconds
- **10,000 dependency resolutions**: < 1.0 seconds
- **10,000 binding resolutions**: < 0.5 seconds
- **100,000 singleton lookups**: < 0.5 seconds
- **1,000,000 bound checks**: < 3.0 seconds

### Memory Efficiency

- **Empty container footprint**: ~5KB
- **10,000 bindings**: < 10MB
- **1,000 singleton resolutions**: < 50KB
- **No memory leaks**: Verified through repeated resolution tests

### Performance Tips

1. Use `get()` instead of `make()` when you want singletons (much faster)
2. Bind frequently-used services early to avoid repeated resolution
3. Use closures for complex instantiation logic only when necessary
4. The container caches all `get()` resolutions automatically

## Error Handling

The container throws clear exceptions for common issues:

### InvalidArgumentException

Thrown when a class doesn't exist:

```php
try {
    $container->get('NonExistentClass');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // "Class NonExistentClass not found"
}
```

### RuntimeException

Thrown when a class cannot be instantiated:

```php
// Abstract class
try {
    $container->get(AbstractLogger::class);
} catch (RuntimeException $e) {
    echo $e->getMessage(); // "Class AbstractLogger is not instantiable"
}

// Unresolvable parameter
class NeedsString
{
    public function __construct(string $name) {}
}

try {
    $container->get(NeedsString::class);
} catch (RuntimeException $e) {
    echo $e->getMessage(); // "Cannot resolve parameter name"
}
```

## Limitations

### What the Container Can Resolve

✅ Classes with no constructor  
✅ Classes with constructor dependencies (other classes)  
✅ Classes with interface dependencies (if bound)  
✅ Classes with optional parameters (default values)  
✅ Nested dependencies (recursive resolution)

### What the Container Cannot Resolve

❌ Abstract classes  
❌ Interfaces without bindings  
❌ Primitive types without default values (string, int, bool, etc.)  
❌ Union types  
❌ Intersection types  
❌ Variadic parameters  
❌ Classes that don't exist

### Workarounds

For unresolvable dependencies, use bindings with closures:

```php
// Problem: Cannot resolve primitive type
class EmailService
{
    public function __construct(
        private string $apiKey,
        private string $fromEmail
    ) {}
}

// Solution: Use closure binding
$container->bind(EmailService::class, function () {
    return new EmailService(
        apiKey: $_ENV['EMAIL_API_KEY'],
        fromEmail: 'noreply@example.com'
    );
});
```

## Testing

The project includes comprehensive tests:

```bash
# Run all tests
composer test

# Run PHPStan static analysis
composer phpstan

# Run both tests and static analysis
composer check
```

### Test Coverage

- **Unit Tests**: Core functionality, edge cases, error handling
- **Feature Tests**: Performance benchmarks, memory efficiency tests
- **Static Analysis**: PHPStan level max with strict rules

## Development

### Requirements

- PHP 8.1+
- Composer
- PHPUnit 10+
- PHPStan 1.10+

### Setup

```bash
# Clone the repository
git clone https://github.com/agussuroyo/container.git
cd container

# Install dependencies
composer install

# Run tests
composer test
```

### Code Quality

The project uses:

- **PSR-4** autoloading
- **Strict types** declaration
- **PHPStan** level max
- **PHPStan strict rules**
- **PHPUnit** for testing

## Architecture

### Design Principles

1. **Simplicity**: Minimal API surface, easy to understand
2. **Performance**: Optimized for speed and memory efficiency
3. **Type Safety**: Full PHP type hints and strict types
4. **Single Responsibility**: Each method has one clear purpose
5. **Fail Fast**: Clear exceptions for invalid operations

### Internal Structure

```php
Container
├── $instances    // Singleton cache (array<string, object>)
├── $bindings     // Interface bindings (array<string, callable>)
├── get()         // Resolve with caching
├── make()        // Create new instance
├── bind()        // Register binding
├── singleton()   // Register singleton
├── bound()       // Check if bound
├── clearInstance() // Clear singleton cache
└── resolveDependencies() // Recursive resolution
```

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass (`composer check`)
5. Submit a pull request

## FAQ

### Q: How is this different from other DI containers?

This container focuses on simplicity, speed, and minimal memory usage. It provides only essential features without unnecessary complexity.

### Q: Does it support constructor promotion?

Yes! The container fully supports PHP 8.1+ constructor property promotion.

### Q: How do I resolve circular dependencies?

The container doesn't handle circular dependencies. Design your classes to avoid circular references, or use setter injection as a workaround.

### Q: Can I clear the singleton cache?

Yes! Use `clearInstance()` to clear a specific singleton or `clearInstance(null)` to clear all singletons. Bindings are preserved, so resolved instances will be recreated on next `get()` call.

### Q: Is it thread-safe?

PHP doesn't have true multi-threading, but each request gets its own container instance, so there are no concurrency issues in typical PHP applications.

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/agussuroyo/container).
