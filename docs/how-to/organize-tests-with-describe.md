# Organize Tests with Describe Blocks

This guide shows how to structure Pest tests using describe blocks while maintaining proper TestLink links.

## Basic Structure

### Describe blocks group related tests

```php
describe('UserService', function () {
    describe('create', function () {
        test('creates user with valid data', function () {
            // ...
        });

        test('validates email format', function () {
            // ...
        });
    });

    describe('update', function () {
        test('updates user name', function () {
            // ...
        });
    });
});
```

### Adding links to described tests

Add `linksAndCovers()` after each test:

```php
describe('UserService', function () {
    describe('create', function () {
        test('creates user with valid data', function () {
            // ...
        })->linksAndCovers(UserService::class.'::create');

        test('validates email format', function () {
            // ...
        })->linksAndCovers(UserService::class.'::create');
    });
});
```

## Test Naming with Describe Blocks

### How Pest names tests

Pest combines describe and test names:

```php
describe('Calculator', function () {
    describe('add', function () {
        test('returns sum of two numbers', function () {
            // ...
        });
    });
});
```

The full test name becomes:
```
Calculator add returns sum of two numbers
```

### Matching in #[TestedBy]

Use the full combined name:

```php
class Calculator
{
    #[TestedBy('Tests\CalculatorTest', 'Calculator add returns sum of two numbers')]
    public function add(int $a, int $b): int
    {
        // ...
    }
}
```

## Recommended Patterns

### Pattern 1: Class > Method > Behavior

```php
describe('OrderService', function () {
    describe('create', function () {
        test('creates order with valid items', function () {
            // ...
        })->linksAndCovers(OrderService::class.'::create');

        test('rejects empty items array', function () {
            // ...
        })->linksAndCovers(OrderService::class.'::create');

        test('calculates total correctly', function () {
            // ...
        })->linksAndCovers(OrderService::class.'::create');
    });

    describe('cancel', function () {
        test('cancels pending order', function () {
            // ...
        })->linksAndCovers(OrderService::class.'::cancel');

        test('throws for shipped order', function () {
            // ...
        })->linksAndCovers(OrderService::class.'::cancel');
    });
});
```

### Pattern 2: Feature > Scenario

```php
describe('User Registration', function () {
    test('allows registration with valid email', function () {
        // ...
    })
    ->linksAndCovers(UserService::class.'::create')
    ->linksAndCovers(UserValidator::class.'::validateEmail');

    test('sends welcome email after registration', function () {
        // ...
    })
    ->linksAndCovers(UserService::class.'::create')
    ->linksAndCovers(EmailService::class.'::sendWelcome');
});
```

### Pattern 3: Context > Action

```php
describe('ShoppingCart', function () {
    describe('when empty', function () {
        test('has zero total', function () {
            // ...
        })->linksAndCovers(ShoppingCart::class.'::getTotal');

        test('has no items', function () {
            // ...
        })->linksAndCovers(ShoppingCart::class.'::getItems');
    });

    describe('with items', function () {
        test('calculates correct total', function () {
            // ...
        })->linksAndCovers(ShoppingCart::class.'::getTotal');

        test('returns all items', function () {
            // ...
        })->linksAndCovers(ShoppingCart::class.'::getItems');
    });
});
```

## Using beforeEach with Links

### Share setup, individual links

```php
describe('UserService', function () {
    beforeEach(function () {
        $this->service = new UserService();
    });

    test('creates user', function () {
        $user = $this->service->create(['name' => 'John']);
        expect($user->name)->toBe('John');
    })->linksAndCovers(UserService::class.'::create');

    test('finds user by ID', function () {
        $user = $this->service->find(1);
        expect($user)->not->toBeNull();
    })->linksAndCovers(UserService::class.'::find');
});
```

## Nested Describe Blocks

### Deep nesting

```php
describe('PaymentService', function () {
    describe('process', function () {
        describe('with valid card', function () {
            test('charges the amount', function () {
                // ...
            })->linksAndCovers(PaymentService::class.'::process');

            test('returns success', function () {
                // ...
            })->linksAndCovers(PaymentService::class.'::process');
        });

        describe('with invalid card', function () {
            test('throws PaymentException', function () {
                // ...
            })->linksAndCovers(PaymentService::class.'::process');
        });
    });
});
```

### Full test names for nested

The test name for the last example:
```
PaymentService process with invalid card throws PaymentException
```

## Tips for Organization

### 1. Mirror production structure

```
src/
  Services/
    UserService.php       → tests/Unit/Services/UserServiceTest.php
    OrderService.php      → tests/Unit/Services/OrderServiceTest.php
```

### 2. One describe per class

```php
// tests/Unit/Services/UserServiceTest.php
describe('UserService', function () {
    // All UserService tests here
});
```

### 3. Group by method

```php
describe('UserService', function () {
    describe('create', function () { /* ... */ });
    describe('update', function () { /* ... */ });
    describe('delete', function () { /* ... */ });
});
```

### 4. Keep describe names short

```php
// Good
describe('create', function () { });

// Avoid
describe('the create method of UserService', function () { });
```

## Validation with Describe Blocks

Run validation to ensure links are correct:

```bash
./vendor/bin/testlink validate
```

Common issues:

| Issue | Cause | Solution |
|-------|-------|----------|
| Test not found | Wrong describe/test name | Check full combined name |
| Orphan TestedBy | Test renamed | Update #[TestedBy] |
| Missing link | Forgot linksAndCovers() | Add the chain |
