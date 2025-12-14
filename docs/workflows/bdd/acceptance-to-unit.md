# Acceptance to Unit Tests

This tutorial shows how to drive unit tests from acceptance tests in a BDD workflow. You'll learn patterns for breaking down high-level behaviors into testable units.

## The Relationship

Acceptance tests describe **what** the system does. Unit tests describe **how** it does it.

```
Acceptance Test: "User can purchase item"
    │
    ├── Unit: Cart validates item availability
    ├── Unit: Payment processes charge
    ├── Unit: Inventory reduces stock
    └── Unit: Order creates confirmation
```

## Linking Strategies

### Strategy 1: Acceptance Links to Service, Units Link to Methods

```php
// Acceptance test - links to the orchestrating method
test('user can checkout cart')
    ->links(CheckoutService::class.'::checkout');

// Unit tests - link to specific methods
test('validates cart is not empty')
    ->linksAndCovers(CheckoutService::class.'::validateCart');

test('calculates total with tax')
    ->linksAndCovers(CheckoutService::class.'::calculateTotal');

test('processes payment')
    ->linksAndCovers(CheckoutService::class.'::processPayment');
```

### Strategy 2: Multiple Acceptance Tests Share Unit Tests

```php
// Multiple acceptance scenarios
test('user can checkout with credit card')
    ->links(PaymentService::class.'::process');

test('user can checkout with PayPal')
    ->links(PaymentService::class.'::process');

// One unit test covers both scenarios
test('processes payment through gateway')
    ->linksAndCovers(PaymentService::class.'::process');
```

## Tutorial: Order Processing

Let's build an order processing feature from acceptance to unit tests.

### Step 1: Define Acceptance Scenarios

Start by identifying the key behaviors:

::: code-group

```php [Pest]
<?php
// tests/Feature/OrderProcessingTest.php

use App\Services\OrderService;
use App\Models\Order;

describe('Order Processing', function () {
    test('user can place order with valid cart', function () {
        $service = new OrderService();

        $order = $service->placeOrder([
            'items' => [
                ['product_id' => 1, 'quantity' => 2, 'price' => 1000],
                ['product_id' => 2, 'quantity' => 1, 'price' => 2500],
            ],
            'customer_email' => 'customer@example.com',
        ]);

        expect($order)->toBeInstanceOf(Order::class);
        expect($order->status)->toBe('confirmed');
        expect($order->total)->toBe(4500);
    })->links(OrderService::class.'::placeOrder');

    test('order fails with empty cart', function () {
        $service = new OrderService();

        expect(fn () => $service->placeOrder([
            'items' => [],
            'customer_email' => 'customer@example.com',
        ]))->toThrow(\InvalidArgumentException::class, 'Cart cannot be empty');
    })->links(OrderService::class.'::placeOrder');

    test('order sends confirmation email', function () {
        $emailService = mock(EmailService::class);
        $emailService->shouldReceive('send')->once();

        $service = new OrderService($emailService);

        $service->placeOrder([
            'items' => [['product_id' => 1, 'quantity' => 1, 'price' => 1000]],
            'customer_email' => 'customer@example.com',
        ]);
    })->links(OrderService::class.'::placeOrder');
});
```

```php [PHPUnit]
<?php
// tests/Feature/OrderProcessingTest.php

namespace Tests\Feature;

use App\Services\OrderService;
use App\Models\Order;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\Links;

class OrderProcessingTest extends TestCase
{
    #[Links(OrderService::class, 'placeOrder')]
    public function test_user_can_place_order_with_valid_cart(): void
    {
        $service = new OrderService();

        $order = $service->placeOrder([
            'items' => [
                ['product_id' => 1, 'quantity' => 2, 'price' => 1000],
                ['product_id' => 2, 'quantity' => 1, 'price' => 2500],
            ],
            'customer_email' => 'customer@example.com',
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame(4500, $order->total);
    }

    #[Links(OrderService::class, 'placeOrder')]
    public function test_order_fails_with_empty_cart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cart cannot be empty');

        $service = new OrderService();

        $service->placeOrder([
            'items' => [],
            'customer_email' => 'customer@example.com',
        ]);
    }
}
```

:::

### Step 2: Identify Unit Tests Needed

From the acceptance tests, we can identify these units:

| Behavior | Unit Test | Method |
|----------|-----------|--------|
| Validate cart | `test_rejects_empty_cart` | `validateCart()` |
| Calculate total | `test_calculates_item_totals` | `calculateTotal()` |
| Create order | `test_creates_order_with_data` | `createOrder()` |
| Send confirmation | `test_sends_email_on_success` | `sendConfirmation()` |

### Step 3: Write Unit Tests

::: code-group

```php [Pest]
<?php
// tests/Unit/OrderServiceTest.php

use App\Services\OrderService;
use App\Models\Order;

describe('OrderService', function () {
    describe('validateCart', function () {
        test('returns true for non-empty items', function () {
            $service = new OrderService();

            $result = $service->validateCart([
                ['product_id' => 1, 'quantity' => 1],
            ]);

            expect($result)->toBeTrue();
        })->linksAndCovers(OrderService::class.'::validateCart');

        test('throws exception for empty items', function () {
            $service = new OrderService();

            expect(fn () => $service->validateCart([]))
                ->toThrow(\InvalidArgumentException::class);
        })->linksAndCovers(OrderService::class.'::validateCart');
    });

    describe('calculateTotal', function () {
        test('sums quantity times price for each item', function () {
            $service = new OrderService();

            $total = $service->calculateTotal([
                ['quantity' => 2, 'price' => 1000],
                ['quantity' => 1, 'price' => 2500],
            ]);

            expect($total)->toBe(4500);
        })->linksAndCovers(OrderService::class.'::calculateTotal');

        test('returns zero for empty items', function () {
            $service = new OrderService();

            expect($service->calculateTotal([]))->toBe(0);
        })->linksAndCovers(OrderService::class.'::calculateTotal');
    });

    describe('createOrder', function () {
        test('creates order with calculated total', function () {
            $service = new OrderService();

            $order = $service->createOrder(
                items: [['product_id' => 1]],
                total: 4500,
                email: 'test@example.com'
            );

            expect($order)->toBeInstanceOf(Order::class);
            expect($order->total)->toBe(4500);
            expect($order->status)->toBe('confirmed');
        })->linksAndCovers(OrderService::class.'::createOrder');
    });

    describe('sendConfirmation', function () {
        test('sends email with order details', function () {
            $emailService = mock(EmailService::class);
            $emailService
                ->shouldReceive('send')
                ->with('test@example.com', Mockery::any())
                ->once();

            $service = new OrderService($emailService);
            $order = new Order(email: 'test@example.com', total: 1000);

            $service->sendConfirmation($order);
        })->linksAndCovers(OrderService::class.'::sendConfirmation');
    });
});
```

```php [PHPUnit]
<?php
// tests/Unit/OrderServiceTest.php

namespace Tests\Unit;

use App\Services\OrderService;
use App\Models\Order;
use PHPUnit\Framework\TestCase;
use TestFlowLabs\TestingAttributes\LinksAndCovers;

class OrderServiceTest extends TestCase
{
    #[LinksAndCovers(OrderService::class, 'validateCart')]
    public function test_returns_true_for_non_empty_items(): void
    {
        $service = new OrderService();

        $result = $service->validateCart([
            ['product_id' => 1, 'quantity' => 1],
        ]);

        $this->assertTrue($result);
    }

    #[LinksAndCovers(OrderService::class, 'validateCart')]
    public function test_throws_exception_for_empty_items(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = new OrderService();
        $service->validateCart([]);
    }

    #[LinksAndCovers(OrderService::class, 'calculateTotal')]
    public function test_sums_quantity_times_price_for_each_item(): void
    {
        $service = new OrderService();

        $total = $service->calculateTotal([
            ['quantity' => 2, 'price' => 1000],
            ['quantity' => 1, 'price' => 2500],
        ]);

        $this->assertSame(4500, $total);
    }

    #[LinksAndCovers(OrderService::class, 'createOrder')]
    public function test_creates_order_with_calculated_total(): void
    {
        $service = new OrderService();

        $order = $service->createOrder(
            items: [['product_id' => 1]],
            total: 4500,
            email: 'test@example.com'
        );

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame(4500, $order->total);
    }
}
```

:::

### Step 4: Implement the Service

```php
<?php
// src/Services/OrderService.php

namespace App\Services;

use App\Models\Order;
use TestFlowLabs\TestingAttributes\TestedBy;

class OrderService
{
    public function __construct(
        private ?EmailService $emailService = null
    ) {}

    #[TestedBy('Tests\Feature\OrderProcessingTest', 'user can place order with valid cart')]
    #[TestedBy('Tests\Feature\OrderProcessingTest', 'order fails with empty cart')]
    #[TestedBy('Tests\Feature\OrderProcessingTest', 'order sends confirmation email')]
    #[TestedBy('Tests\Unit\OrderServiceTest', 'validates email before registration')]
    public function placeOrder(array $data): Order
    {
        $this->validateCart($data['items']);

        $total = $this->calculateTotal($data['items']);

        $order = $this->createOrder(
            items: $data['items'],
            total: $total,
            email: $data['customer_email']
        );

        if ($this->emailService) {
            $this->sendConfirmation($order);
        }

        return $order;
    }

    #[TestedBy('Tests\Unit\OrderServiceTest', 'returns true for non-empty items')]
    #[TestedBy('Tests\Unit\OrderServiceTest', 'throws exception for empty items')]
    public function validateCart(array $items): bool
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('Cart cannot be empty');
        }

        return true;
    }

    #[TestedBy('Tests\Unit\OrderServiceTest', 'sums quantity times price for each item')]
    #[TestedBy('Tests\Unit\OrderServiceTest', 'returns zero for empty items')]
    public function calculateTotal(array $items): int
    {
        return array_reduce($items, function ($sum, $item) {
            return $sum + ($item['quantity'] * $item['price']);
        }, 0);
    }

    #[TestedBy('Tests\Unit\OrderServiceTest', 'creates order with calculated total')]
    public function createOrder(array $items, int $total, string $email): Order
    {
        return new Order(
            items: $items,
            total: $total,
            email: $email,
            status: 'confirmed'
        );
    }

    #[TestedBy('Tests\Unit\OrderServiceTest', 'sends email with order details')]
    public function sendConfirmation(Order $order): void
    {
        $this->emailService->send(
            $order->email,
            "Order confirmed! Total: {$order->total}"
        );
    }
}
```

### Step 5: View the Complete Picture

```bash
./vendor/bin/testlink report
```

```
Coverage Links Report
─────────────────────

App\Services\OrderService

  placeOrder()
    → Tests\Feature\OrderProcessingTest::user can place order with valid cart
    → Tests\Feature\OrderProcessingTest::order fails with empty cart
    → Tests\Feature\OrderProcessingTest::order sends confirmation email

  validateCart()
    → Tests\Unit\OrderServiceTest::returns true for non-empty items
    → Tests\Unit\OrderServiceTest::throws exception for empty items

  calculateTotal()
    → Tests\Unit\OrderServiceTest::sums quantity times price for each item
    → Tests\Unit\OrderServiceTest::returns zero for empty items

  createOrder()
    → Tests\Unit\OrderServiceTest::creates order with calculated total

  sendConfirmation()
    → Tests\Unit\OrderServiceTest::sends email with order details

Summary
  Methods with tests: 5
  Total test links: 10
```

## Patterns for Acceptance → Unit

### Pattern 1: Decompose by Responsibility

```
Acceptance: User registers
    ├── Unit: Validates email format
    ├── Unit: Checks email uniqueness
    ├── Unit: Hashes password
    └── Unit: Creates user record
```

### Pattern 2: Decompose by Error Cases

```
Acceptance: User makes payment
    ├── Unit: Validates card number
    ├── Unit: Checks expiry date
    ├── Unit: Verifies CVV
    └── Unit: Handles gateway errors
```

### Pattern 3: Decompose by Integration Points

```
Acceptance: Order is fulfilled
    ├── Unit: Updates inventory
    ├── Unit: Charges payment
    ├── Unit: Creates shipment
    └── Unit: Sends notification
```

## What's Next?

- [Placeholder BDD](./placeholders) - Use placeholders during BDD
- [Complete Example](./complete-example) - Full BDD example with shopping cart
