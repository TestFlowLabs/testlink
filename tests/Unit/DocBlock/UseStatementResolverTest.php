<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\DocBlock\UseStatementResolver;

describe('UseStatementResolver', function (): void {
    beforeEach(function (): void {
        $this->resolver   = new UseStatementResolver();
        $this->fixtureDir = sys_get_temp_dir().'/testlink-fixtures-'.uniqid();
        mkdir($this->fixtureDir, 0777, true);
    });

    afterEach(function (): void {
        // Clean up fixture files
        $files = glob($this->fixtureDir.'/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->fixtureDir);
    });

    describe('resolve', function (): void {
        it('returns FQCN unchanged when already starts with backslash', function (): void {
            $result = $this->resolver->resolve('\Tests\Unit\UserTest::testCreate', '/any/file.php');

            expect($result['fqcn'])->toBe('\Tests\Unit\UserTest::testCreate');
            expect($result['error'])->toBeNull();
        });

        it('resolves short class name using use statements', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

use Tests\Unit\UserTest;

class UserService
{
    /**
     * @see UserTest::testCreate
     */
    public function create(): void {}
}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            $result = $this->resolver->resolve('UserTest::testCreate', $filePath);

            expect($result['fqcn'])->toBe('\Tests\Unit\UserTest::testCreate');
            expect($result['error'])->toBeNull();
        });

        it('resolves aliased imports', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

use Tests\Unit\UserServiceTest as UserTest;

class UserService
{
    /**
     * @see UserTest::testCreate
     */
    public function create(): void {}
}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            $result = $this->resolver->resolve('UserTest::testCreate', $filePath);

            expect($result['fqcn'])->toBe('\Tests\Unit\UserServiceTest::testCreate');
            expect($result['error'])->toBeNull();
        });

        it('resolves grouped imports', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

use Tests\Unit\{UserTest, OrderTest};

class UserService
{
    /**
     * @see UserTest::testCreate
     */
    public function create(): void {}
}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            $result = $this->resolver->resolve('UserTest::testCreate', $filePath);

            expect($result['fqcn'])->toBe('\Tests\Unit\UserTest::testCreate');
            expect($result['error'])->toBeNull();
        });

        it('resolves class-only reference', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

use Tests\Unit\UserTest;

class UserService {}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            $result = $this->resolver->resolve('UserTest', $filePath);

            expect($result['fqcn'])->toBe('\Tests\Unit\UserTest');
            expect($result['error'])->toBeNull();
        });

        it('returns error for method-only reference', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

class UserService {}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            $result = $this->resolver->resolve('testCreate', $filePath);

            expect($result['fqcn'])->toBeNull();
            expect($result['error'])->toBe('Method-only reference cannot be resolved');
        });

        it('returns error for unresolvable class', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

use Tests\Unit\OrderTest;

class UserService {}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            $result = $this->resolver->resolve('UserTest::testCreate', $filePath);

            expect($result['fqcn'])->toBeNull();
            expect($result['error'])->toContain("Could not resolve 'UserTest'");
        });

        it('returns error for non-existent file', function (): void {
            $result = $this->resolver->resolve('UserTest::testCreate', '/non/existent/file.php');

            expect($result['fqcn'])->toBeNull();
            expect($result['error'])->toBe('Could not parse file');
        });

        it('strips parentheses from method name', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

use Tests\Unit\UserTest;

class UserService {}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            $result = $this->resolver->resolve('UserTest::testCreate()', $filePath);

            expect($result['fqcn'])->toBe('\Tests\Unit\UserTest::testCreate');
            expect($result['error'])->toBeNull();
        });
    });

    describe('caching', function (): void {
        it('caches parsed file info', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

use Tests\Unit\UserTest;

class UserService {}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            // First resolve
            $result1 = $this->resolver->resolve('UserTest::testCreate', $filePath);

            // Modify file (cache should still return old result)
            file_put_contents($filePath, str_replace('UserTest', 'OtherTest', $code));

            // Second resolve (should use cached result)
            $result2 = $this->resolver->resolve('UserTest::testCreate', $filePath);

            expect($result1['fqcn'])->toBe($result2['fqcn']);
        });

        it('clears cache', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

use Tests\Unit\UserTest;

class UserService {}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            // First resolve
            $this->resolver->resolve('UserTest::testCreate', $filePath);

            // Modify file
            $newCode = str_replace('UserTest', 'OtherTest', $code);
            file_put_contents($filePath, $newCode);

            // Clear cache
            $this->resolver->clearCache();

            // Should now return error since UserTest is no longer imported
            $result = $this->resolver->resolve('UserTest::testCreate', $filePath);

            expect($result['fqcn'])->toBeNull();
        });
    });

    describe('global classes', function (): void {
        it('resolves global DateTime class', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

class UserService {}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            $result = $this->resolver->resolve('DateTime', $filePath);

            expect($result['fqcn'])->toBe('\DateTime');
            expect($result['error'])->toBeNull();
        });
    });
});
