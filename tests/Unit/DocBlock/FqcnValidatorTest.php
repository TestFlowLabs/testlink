<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\DocBlock\FqcnIssue;
use TestFlowLabs\TestLink\DocBlock\SeeTagEntry;
use TestFlowLabs\TestLink\DocBlock\FqcnValidator;
use TestFlowLabs\TestLink\DocBlock\SeeTagRegistry;
use TestFlowLabs\TestLink\DocBlock\FqcnIssueRegistry;

describe('FqcnValidator', function (): void {
    beforeEach(function (): void {
        $this->fixtureDir = sys_get_temp_dir().'/testlink-fqcn-fixtures-'.uniqid();
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

    describe('validate', function (): void {
        it('returns empty registry when all @see tags are FQCN', function (): void {
            $seeRegistry = new SeeTagRegistry();
            $seeRegistry->registerProductionSee(
                'App\UserService::create',
                new SeeTagEntry(
                    reference: '\Tests\Unit\UserTest::testCreate',
                    filePath: '/app/src/UserService.php',
                    line: 42,
                    context: 'production',
                    methodName: 'create',
                    className: 'App\UserService',
                )
            );

            $validator = new FqcnValidator();
            $issues    = $validator->validate($seeRegistry);

            expect($issues->hasIssues())->toBeFalse();
            expect($issues->count())->toBe(0);
        });

        it('detects non-FQCN @see tags in production', function (): void {
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

            $seeRegistry = new SeeTagRegistry();
            $seeRegistry->registerProductionSee(
                'App\Services\UserService::create',
                new SeeTagEntry(
                    reference: 'UserTest::testCreate',
                    filePath: $filePath,
                    line: 10,
                    context: 'production',
                    methodName: 'create',
                    className: 'App\Services\UserService',
                )
            );

            $validator = new FqcnValidator();
            $issues    = $validator->validate($seeRegistry);

            expect($issues->hasIssues())->toBeTrue();
            expect($issues->count())->toBe(1);

            $issue = $issues->getIssuesForFile($filePath)[0];

            expect($issue->originalReference)->toBe('UserTest::testCreate');
            expect($issue->resolvedFqcn)->toBe('\Tests\Unit\UserTest::testCreate');
            expect($issue->isFixable())->toBeTrue();
        });

        it('detects non-FQCN @see tags in tests', function (): void {
            $code = <<<'PHP'
<?php

namespace Tests\Unit;

use App\Services\UserService;

class UserTest
{
    /**
     * @see UserService::create
     */
    public function testCreate(): void {}
}
PHP;
            $filePath = $this->fixtureDir.'/UserTest.php';
            file_put_contents($filePath, $code);

            $seeRegistry = new SeeTagRegistry();
            $seeRegistry->registerTestSee(
                'Tests\Unit\UserTest::testCreate',
                new SeeTagEntry(
                    reference: 'UserService::create',
                    filePath: $filePath,
                    line: 10,
                    context: 'test',
                    methodName: 'testCreate',
                    className: 'Tests\Unit\UserTest',
                )
            );

            $validator = new FqcnValidator();
            $issues    = $validator->validate($seeRegistry);

            expect($issues->hasIssues())->toBeTrue();
            expect($issues->count())->toBe(1);

            $issue = $issues->getIssuesForFile($filePath)[0];

            expect($issue->context)->toBe('test');
            expect($issue->resolvedFqcn)->toBe('\App\Services\UserService::create');
        });

        it('marks unresolvable references as not fixable', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

class UserService {}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            $seeRegistry = new SeeTagRegistry();
            $seeRegistry->registerProductionSee(
                'App\Services\UserService::create',
                new SeeTagEntry(
                    reference: 'UnknownClass::method',
                    filePath: $filePath,
                    line: 5,
                    context: 'production',
                )
            );

            $validator = new FqcnValidator();
            $issues    = $validator->validate($seeRegistry);

            expect($issues->hasIssues())->toBeTrue();

            $issue = $issues->getIssuesForFile($filePath)[0];

            expect($issue->isFixable())->toBeFalse();
            expect($issue->errorMessage)->toContain("Could not resolve 'UnknownClass'");
        });
    });

    describe('fix', function (): void {
        it('replaces short class names with FQCN', function (): void {
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

            $issueRegistry = new FqcnIssueRegistry();
            $issueRegistry->register(new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: $filePath,
                line: 10,
                context: 'production',
                isResolvable: true,
            ));

            $validator = new FqcnValidator();
            $result    = $validator->fix($issueRegistry);

            expect($result['fixed'])->toBe(1);
            expect($result['files'])->toHaveKey($filePath);
            expect($result['errors'])->toBe([]);

            // Verify file was modified
            $modifiedCode = file_get_contents($filePath);

            expect($modifiedCode)->toContain('@see \Tests\Unit\UserTest::testCreate');
            expect($modifiedCode)->not->toContain('@see UserTest::testCreate');
        });

        it('preserves indentation when fixing', function (): void {
            $code = <<<'PHP'
<?php

namespace App\Services;

use Tests\Unit\UserTest;

class UserService
{
    /**
     * Some description.
     *
     * @see UserTest::testCreate
     */
    public function create(): void {}
}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            $issueRegistry = new FqcnIssueRegistry();
            $issueRegistry->register(new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: $filePath,
                line: 12,
                context: 'production',
                isResolvable: true,
            ));

            $validator = new FqcnValidator();
            $validator->fix($issueRegistry);

            $modifiedCode = file_get_contents($filePath);

            // Check that indentation is preserved (4 spaces + ' * ')
            expect($modifiedCode)->toContain('     * @see \Tests\Unit\UserTest::testCreate');
        });

        it('does not modify files in dry-run mode', function (): void {
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

            $issueRegistry = new FqcnIssueRegistry();
            $issueRegistry->register(new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: $filePath,
                line: 10,
                context: 'production',
                isResolvable: true,
            ));

            $validator = new FqcnValidator();
            $result    = $validator->fix($issueRegistry, dryRun: true);

            expect($result['fixed'])->toBe(1);

            // Verify file was NOT modified
            $originalCode = file_get_contents($filePath);

            expect($originalCode)->toContain('@see UserTest::testCreate');
            expect($originalCode)->not->toContain('@see \Tests\Unit\UserTest::testCreate');
        });

        it('skips unfixable issues', function (): void {
            $issueRegistry = new FqcnIssueRegistry();
            $issueRegistry->register(new FqcnIssue(
                originalReference: 'UnknownClass::method',
                resolvedFqcn: null,
                filePath: '/app/src/UserService.php',
                line: 10,
                context: 'production',
                isResolvable: false,
                errorMessage: 'Not found',
            ));

            $validator = new FqcnValidator();
            $result    = $validator->fix($issueRegistry);

            expect($result['fixed'])->toBe(0);
            expect($result['files'])->toBe([]);
        });

        it('fixes multiple issues in same file', function (): void {
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

    /**
     * @see UserTest::testUpdate
     */
    public function update(): void {}
}
PHP;
            $filePath = $this->fixtureDir.'/UserService.php';
            file_put_contents($filePath, $code);

            $issueRegistry = new FqcnIssueRegistry();
            $issueRegistry->register(new FqcnIssue(
                originalReference: 'UserTest::testCreate',
                resolvedFqcn: '\Tests\Unit\UserTest::testCreate',
                filePath: $filePath,
                line: 10,
                context: 'production',
                isResolvable: true,
            ));
            $issueRegistry->register(new FqcnIssue(
                originalReference: 'UserTest::testUpdate',
                resolvedFqcn: '\Tests\Unit\UserTest::testUpdate',
                filePath: $filePath,
                line: 15,
                context: 'production',
                isResolvable: true,
            ));

            $validator = new FqcnValidator();
            $result    = $validator->fix($issueRegistry);

            expect($result['fixed'])->toBe(2);

            $modifiedCode = file_get_contents($filePath);

            expect($modifiedCode)->toContain('@see \Tests\Unit\UserTest::testCreate');
            expect($modifiedCode)->toContain('@see \Tests\Unit\UserTest::testUpdate');
        });
    });
});
