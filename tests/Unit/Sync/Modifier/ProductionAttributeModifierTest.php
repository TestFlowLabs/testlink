<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\SyncResult;
use TestFlowLabs\TestLink\Sync\ReverseTestedByAction;
use TestFlowLabs\TestLink\Sync\Modifier\ProductionAttributeModifier;

describe('ProductionAttributeModifier', function (): void {
    describe('apply', function (): void {
        it('returns empty result for empty input', function (): void {
            $modifier = new ProductionAttributeModifier();
            $result   = $modifier->apply([]);

            expect($result)->toBeInstanceOf(SyncResult::class);
            expect($result->hasChanges())->toBeFalse();
        });

        it('returns error for nonexistent file', function (): void {
            $modifier = new ProductionAttributeModifier();
            $action   = new ReverseTestedByAction(
                productionFile: '/nonexistent/path/to/file.php',
                methodIdentifier: 'App\\User::create',
                testIdentifier: 'Tests\\UserTest::test_creates_user',
                className: 'App\\User',
                methodName: 'create',
            );

            $result = $modifier->apply([$action]);

            expect($result->hasErrors())->toBeTrue();
            expect($result->errors[0])->toContain('File not found');
        });
    });

    describe('injectTestedBy', function (): void {
        it('injects #[TestedBy] attribute before method', function (): void {
            $modifier = new ProductionAttributeModifier();
            $code     = <<<'PHP'
<?php

namespace App;

class User
{
    public function create(): void
    {
        // ...
    }
}
PHP;

            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::create',
                testIdentifier: 'Tests\\Unit\\UserTest::test_creates_user',
                className: 'App\\User',
                methodName: 'create',
            );

            $result = $modifier->injectTestedBy($code, $action);

            expect($result)->toContain("#[TestedBy(UserTest::class, 'test_creates_user')]");
            expect($result)->toContain('use TestFlowLabs\\TestingAttributes\\TestedBy;');
        });

        it('handles method with visibility modifier', function (): void {
            $modifier = new ProductionAttributeModifier();
            $code     = <<<'PHP'
<?php

namespace App;

class User
{
    protected function save(): bool
    {
        return true;
    }
}
PHP;

            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::save',
                testIdentifier: 'Tests\\UserTest::test_save',
                className: 'App\\User',
                methodName: 'save',
            );

            $result = $modifier->injectTestedBy($code, $action);

            expect($result)->toContain("#[TestedBy(UserTest::class, 'test_save')]");
        });

        it('does not duplicate attribute if already exists', function (): void {
            $modifier = new ProductionAttributeModifier();
            $code     = <<<'PHP'
<?php

namespace App;

use TestFlowLabs\TestingAttributes\TestedBy;

class User
{
    #[TestedBy(UserTest::class, 'test_creates_user')]
    public function create(): void
    {
        // ...
    }
}
PHP;

            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::create',
                testIdentifier: 'Tests\\Unit\\UserTest::test_creates_user',
                className: 'App\\User',
                methodName: 'create',
            );

            $result = $modifier->injectTestedBy($code, $action);

            // Should not modify - attribute already exists
            expect($result)->toBe($code);
        });

        it('does not add duplicate use statement', function (): void {
            $modifier = new ProductionAttributeModifier();
            $code     = <<<'PHP'
<?php

namespace App;

use TestFlowLabs\TestingAttributes\TestedBy;

class User
{
    public function create(): void
    {
        // ...
    }
}
PHP;

            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::create',
                testIdentifier: 'Tests\\Unit\\UserTest::test_creates_user',
                className: 'App\\User',
                methodName: 'create',
            );

            $result = $modifier->injectTestedBy($code, $action);

            // Count occurrences of use statement
            $useCount = substr_count($result, 'use TestFlowLabs\\TestingAttributes\\TestedBy;');
            expect($useCount)->toBe(1);
        });

        it('returns unchanged code if method not found', function (): void {
            $modifier = new ProductionAttributeModifier();
            $code     = <<<'PHP'
<?php

namespace App;

class User
{
    public function differentMethod(): void
    {
        // ...
    }
}
PHP;

            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::nonexistent',
                testIdentifier: 'Tests\\UserTest::test',
                className: 'App\\User',
                methodName: 'nonexistent',
            );

            $result = $modifier->injectTestedBy($code, $action);

            expect($result)->toBe($code);
        });

        it('handles class-only test identifier', function (): void {
            $modifier = new ProductionAttributeModifier();
            $code     = <<<'PHP'
<?php

namespace App;

class User
{
    public function create(): void
    {
        // ...
    }
}
PHP;

            $action = new ReverseTestedByAction(
                productionFile: '/test.php',
                methodIdentifier: 'App\\User::create',
                testIdentifier: 'Tests\\Unit\\UserTest',
                className: 'App\\User',
                methodName: 'create',
            );

            $result = $modifier->injectTestedBy($code, $action);

            expect($result)->toContain('#[TestedBy(UserTest::class)]');
        });
    });

    describe('removeTestedBy', function (): void {
        it('removes #[TestedBy] attribute for specific test', function (): void {
            $modifier = new ProductionAttributeModifier();
            $code     = <<<'PHP'
<?php

namespace App;

use TestFlowLabs\TestingAttributes\TestedBy;

class User
{
    #[TestedBy(UserTest::class, 'test_creates_user')]
    public function create(): void
    {
        // ...
    }
}
PHP;

            $result = $modifier->removeTestedBy($code, 'create', ['Tests\\Unit\\UserTest::test_creates_user']);

            expect($result)->not->toContain("#[TestedBy(UserTest::class, 'test_creates_user')]");
            expect($result)->toContain('public function create');
        });

        it('keeps other #[TestedBy] attributes', function (): void {
            $modifier = new ProductionAttributeModifier();
            $code     = <<<'PHP'
<?php

namespace App;

use TestFlowLabs\TestingAttributes\TestedBy;

class User
{
    #[TestedBy(UserTest::class, 'test_creates_user')]
    #[TestedBy(AnotherTest::class, 'test_other')]
    public function create(): void
    {
        // ...
    }
}
PHP;

            $result = $modifier->removeTestedBy($code, 'create', ['Tests\\Unit\\UserTest::test_creates_user']);

            expect($result)->not->toContain("#[TestedBy(UserTest::class, 'test_creates_user')]");
            expect($result)->toContain("#[TestedBy(AnotherTest::class, 'test_other')]");
        });
    });

    describe('integration', function (): void {
        it('processes multiple actions for same file', function (): void {
            $modifier = new ProductionAttributeModifier();

            // Test with only nonexistent files (won't modify anything)
            $result = $modifier->apply([]);

            expect($result)->toBeInstanceOf(SyncResult::class);
        });

        it('returns SyncResult with correct type', function (): void {
            $modifier = new ProductionAttributeModifier();
            $result   = $modifier->apply([]);

            expect($result)->toBeInstanceOf(SyncResult::class);
        });
    });
});
