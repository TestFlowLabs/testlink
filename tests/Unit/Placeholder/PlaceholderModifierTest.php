<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Placeholder\PlaceholderEntry;
use TestFlowLabs\TestLink\Placeholder\PlaceholderAction;
use TestFlowLabs\TestLink\Placeholder\PlaceholderModifier;

describe('PlaceholderModifier', function (): void {
    beforeEach(function (): void {
        $this->tempDir = sys_get_temp_dir().'/testlink_modifier_test_'.uniqid();
        mkdir($this->tempDir, 0777, true);
    });

    afterEach(function (): void {
        // Clean up temp files
        $files = glob($this->tempDir.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    });

    describe('apply', function (): void {
        it('replaces placeholder in production TestedBy attribute')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $prodFile = $this->tempDir.'/UserService.php';
                file_put_contents($prodFile, <<<'PHP'
<?php
class UserService
{
    #[TestedBy('@A')]
    public function create(): void {}
}
PHP);

                $prodEntry = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService::create', $prodFile, 4, 'production', null
                );
                $testEntry = new PlaceholderEntry(
                    '@A', 'Tests\\Unit\\UserServiceTest::it creates user', '/test.php', 10, 'test', 'pest'
                );
                $action = new PlaceholderAction('@A', $prodEntry, $testEntry);

                $modifier = new PlaceholderModifier();
                $result   = $modifier->apply([$action], dryRun: false);

                $content = file_get_contents($prodFile);

                return [
                    'contains_new'   => str_contains($content, "TestedBy('Tests\\Unit\\UserServiceTest', 'it creates user')"),
                    'no_placeholder' => !str_contains($content, '@A'),
                    'file_modified'  => count($result['modified_files']) > 0,
                ];
            })
            ->toMatchArray([
                'contains_new'   => true,
                'no_placeholder' => true,
                'file_modified'  => true,
            ]);

        it('replaces placeholder in Pest linksAndCovers call')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $testFile = $this->tempDir.'/UserServiceTest.php';
                file_put_contents($testFile, <<<'PHP'
<?php
test('creates user', function () {
    // test
})->linksAndCovers('@A');
PHP);

                $prodEntry = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService::create', '/prod.php', 4, 'production', null
                );
                $testEntry = new PlaceholderEntry(
                    '@A', 'Tests\\Unit\\UserServiceTest::creates user', $testFile, 4, 'test', 'pest'
                );
                $action = new PlaceholderAction('@A', $prodEntry, $testEntry);

                $modifier = new PlaceholderModifier();
                $result   = $modifier->apply([$action], dryRun: false);

                $content = file_get_contents($testFile);

                return [
                    'contains_class'  => str_contains($content, 'App\\Services\\UserService::class'),
                    'contains_method' => str_contains($content, "'::create'"),
                    'no_placeholder'  => !str_contains($content, '@A'),
                ];
            })
            ->toMatchArray([
                'contains_class'  => true,
                'contains_method' => true,
                'no_placeholder'  => true,
            ]);

        it('replaces placeholder in Pest links call')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $testFile = $this->tempDir.'/IntegrationTest.php';
                file_put_contents($testFile, <<<'PHP'
<?php
test('integration test')->links('@A');
PHP);

                $prodEntry = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService::create', '/prod.php', 4, 'production', null
                );
                $testEntry = new PlaceholderEntry(
                    '@A', 'Tests\\Integration\\IntegrationTest::integration test', $testFile, 2, 'test', 'pest'
                );
                $action = new PlaceholderAction('@A', $prodEntry, $testEntry);

                $modifier = new PlaceholderModifier();
                $modifier->apply([$action], dryRun: false);

                $content = file_get_contents($testFile);

                return str_contains($content, '->links(App\\Services\\UserService::class');
            })
            ->toBeTrue();

        it('replaces placeholder in PHPUnit LinksAndCovers attribute')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $testFile = $this->tempDir.'/UserServicePhpUnitTest.php';
                file_put_contents($testFile, <<<'PHP'
<?php
namespace Tests\Unit;

class UserServiceTest extends TestCase
{
    #[LinksAndCovers('@A')]
    public function testCreatesUser(): void {}
}
PHP);

                $prodEntry = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService::create', '/prod.php', 4, 'production', null
                );
                $testEntry = new PlaceholderEntry(
                    '@A', 'Tests\\Unit\\UserServiceTest::testCreatesUser', $testFile, 7, 'test', 'phpunit'
                );
                $action = new PlaceholderAction('@A', $prodEntry, $testEntry);

                $modifier = new PlaceholderModifier();
                $modifier->apply([$action], dryRun: false);

                $content = file_get_contents($testFile);

                return [
                    'contains_class'  => str_contains($content, 'UserService::class'),
                    'contains_method' => str_contains($content, "'create'"),
                    'no_placeholder'  => !str_contains($content, '@A'),
                ];
            })
            ->toMatchArray([
                'contains_class'  => true,
                'contains_method' => true,
                'no_placeholder'  => true,
            ]);

        it('does not modify files in dry-run mode')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $prodFile = $this->tempDir.'/Service.php';
                file_put_contents($prodFile, <<<'PHP'
<?php
class Service
{
    #[TestedBy('@A')]
    public function method(): void {}
}
PHP);
                $originalContent = file_get_contents($prodFile);

                $prodEntry = new PlaceholderEntry(
                    '@A', 'Service::method', $prodFile, 4, 'production', null
                );
                $testEntry = new PlaceholderEntry(
                    '@A', 'Test::test', '/test.php', 10, 'test', 'pest'
                );
                $action = new PlaceholderAction('@A', $prodEntry, $testEntry);

                $modifier = new PlaceholderModifier();
                $result   = $modifier->apply([$action], dryRun: true);

                $currentContent = file_get_contents($prodFile);

                return [
                    'unchanged'   => $currentContent === $originalContent,
                    'has_changes' => count($result['changes']) > 0,
                ];
            })
            ->toMatchArray([
                'unchanged'   => true,
                'has_changes' => true,
            ]);

        it('handles multiple actions for same placeholder (N:M)')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $prodFile = $this->tempDir.'/MultiService.php';
                file_put_contents($prodFile, <<<'PHP'
<?php
class MultiService
{
    #[TestedBy('@A')]
    public function method(): void {}
}
PHP);

                $prodEntry = new PlaceholderEntry(
                    '@A', 'MultiService::method', $prodFile, 4, 'production', null
                );

                // Two tests with same placeholder
                $testEntry1 = new PlaceholderEntry(
                    '@A', 'Test1::test1', '/test1.php', 10, 'test', 'pest'
                );
                $testEntry2 = new PlaceholderEntry(
                    '@A', 'Test2::test2', '/test2.php', 20, 'test', 'pest'
                );

                $action1 = new PlaceholderAction('@A', $prodEntry, $testEntry1);
                $action2 = new PlaceholderAction('@A', $prodEntry, $testEntry2);

                $modifier = new PlaceholderModifier();
                $modifier->apply([$action1, $action2], dryRun: false);

                $content = file_get_contents($prodFile);

                return [
                    'has_test1' => str_contains($content, 'Test1'),
                    'has_test2' => str_contains($content, 'Test2'),
                ];
            })
            ->toMatchArray([
                'has_test1' => true,
                'has_test2' => true,
            ]);

        it('returns empty result for non-existent file')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $prodEntry = new PlaceholderEntry(
                    '@A', 'Service::method', '/nonexistent.php', 4, 'production', null
                );
                $testEntry = new PlaceholderEntry(
                    '@A', 'Test::test', '/also-nonexistent.php', 10, 'test', 'pest'
                );
                $action = new PlaceholderAction('@A', $prodEntry, $testEntry);

                $modifier = new PlaceholderModifier();
                $result   = $modifier->apply([$action], dryRun: false);

                return count($result['modified_files']);
            })
            ->toBe(0);

        it('adds use statement for PHPUnit when class not imported')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $testFile = $this->tempDir.'/PhpUnitTest.php';
                file_put_contents($testFile, <<<'PHP'
<?php
namespace Tests\Unit;

class ServiceTest extends TestCase
{
    #[LinksAndCovers('@A')]
    public function testMethod(): void {}
}
PHP);

                $prodEntry = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService::create', '/prod.php', 4, 'production', null
                );
                $testEntry = new PlaceholderEntry(
                    '@A', 'Tests\\Unit\\ServiceTest::testMethod', $testFile, 7, 'test', 'phpunit'
                );
                $action = new PlaceholderAction('@A', $prodEntry, $testEntry);

                $modifier = new PlaceholderModifier();
                $modifier->apply([$action], dryRun: false);

                $content = file_get_contents($testFile);

                return str_contains($content, 'use App\\Services\\UserService;');
            })
            ->toBeTrue();

        it('replaces chained Pest placeholders with separate methods')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $testFile = $this->tempDir.'/ChainedTest.php';
                file_put_contents($testFile, <<<'PHP'
<?php
test('chained test', function () {
    // test
})->linksAndCovers('@A')
  ->linksAndCovers('@A');
PHP);

                $prodEntry1 = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService::methodA', '/prod.php', 4, 'production', null
                );
                $prodEntry2 = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService::methodB', '/prod.php', 8, 'production', null
                );
                $testEntry = new PlaceholderEntry(
                    '@A', 'Tests\\ChainedTest::chained test', $testFile, 4, 'test', 'pest'
                );

                $action1 = new PlaceholderAction('@A', $prodEntry1, $testEntry);
                $action2 = new PlaceholderAction('@A', $prodEntry2, $testEntry);

                $modifier = new PlaceholderModifier();
                $modifier->apply([$action1, $action2], dryRun: false);

                $content = file_get_contents($testFile);

                return [
                    'has_methodA'    => str_contains($content, "'::methodA'"),
                    'has_methodB'    => str_contains($content, "'::methodB'"),
                    'no_placeholder' => !str_contains($content, '@A'),
                    'count_links'    => substr_count($content, 'linksAndCovers'),
                ];
            })
            ->toMatchArray([
                'has_methodA'    => true,
                'has_methodB'    => true,
                'no_placeholder' => true,
                'count_links'    => 2,
            ]);

        it('chains all methods for single placeholder with multiple methods')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $testFile = $this->tempDir.'/SinglePlaceholderTest.php';
                file_put_contents($testFile, <<<'PHP'
<?php
test('single placeholder test', function () {
    // test
})->linksAndCovers('@A');
PHP);

                $prodEntry1 = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService::methodA', '/prod.php', 4, 'production', null
                );
                $prodEntry2 = new PlaceholderEntry(
                    '@A', 'App\\Services\\UserService::methodB', '/prod.php', 8, 'production', null
                );
                $testEntry = new PlaceholderEntry(
                    '@A', 'Tests\\SinglePlaceholderTest::single placeholder test', $testFile, 4, 'test', 'pest'
                );

                $action1 = new PlaceholderAction('@A', $prodEntry1, $testEntry);
                $action2 = new PlaceholderAction('@A', $prodEntry2, $testEntry);

                $modifier = new PlaceholderModifier();
                $modifier->apply([$action1, $action2], dryRun: false);

                $content = file_get_contents($testFile);

                return [
                    'has_methodA'    => str_contains($content, "'::methodA'"),
                    'has_methodB'    => str_contains($content, "'::methodB'"),
                    'no_placeholder' => !str_contains($content, '@A'),
                    'count_links'    => substr_count($content, 'linksAndCovers'),
                ];
            })
            ->toMatchArray([
                'has_methodA'    => true,
                'has_methodB'    => true,
                'no_placeholder' => true,
                'count_links'    => 2,
            ]);

        it('replaces @@prefix placeholder with @see tag in production code')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $prodFile = $this->tempDir.'/SeeTagService.php';
                file_put_contents($prodFile, <<<'PHP'
<?php
class SeeTagService
{
    #[TestedBy('@@A')]
    public function create(): void {}
}
PHP);

                $prodEntry = new PlaceholderEntry(
                    '@@A', 'App\\Services\\SeeTagService::create', $prodFile, 4, 'production', null, true
                );
                $testEntry = new PlaceholderEntry(
                    '@@A', 'Tests\\Unit\\SeeTagServiceTest::testCreate', '/test.php', 10, 'test', 'phpunit', true
                );
                $action = new PlaceholderAction('@@A', $prodEntry, $testEntry);

                $modifier = new PlaceholderModifier();
                $modifier->apply([$action], dryRun: false);

                $content = file_get_contents($prodFile);

                return [
                    'has_see_tag'    => str_contains($content, '@see'),
                    'has_fqcn'       => str_contains($content, '\\Tests\\Unit\\SeeTagServiceTest::testCreate'),
                    'no_testedby'    => !str_contains($content, '#[TestedBy'),
                    'no_placeholder' => !str_contains($content, '@@A'),
                ];
            })
            ->toMatchArray([
                'has_see_tag'    => true,
                'has_fqcn'       => true,
                'no_testedby'    => true,
                'no_placeholder' => true,
            ]);

        it('replaces @@prefix placeholder with @see tag in PHPUnit test')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $testFile = $this->tempDir.'/SeeTagPhpUnitTest.php';
                file_put_contents($testFile, <<<'PHP'
<?php
namespace Tests\Unit;

class SeeTagServiceTest extends TestCase
{
    #[LinksAndCovers('@@A')]
    public function testCreate(): void {}
}
PHP);

                $prodEntry = new PlaceholderEntry(
                    '@@A', 'App\\Services\\UserService::create', '/prod.php', 4, 'production', null, true
                );
                $testEntry = new PlaceholderEntry(
                    '@@A', 'Tests\\Unit\\SeeTagServiceTest::testCreate', $testFile, 7, 'test', 'phpunit', true
                );
                $action = new PlaceholderAction('@@A', $prodEntry, $testEntry);

                $modifier = new PlaceholderModifier();
                $modifier->apply([$action], dryRun: false);

                $content = file_get_contents($testFile);

                return [
                    'has_see_tag'    => str_contains($content, '@see'),
                    'has_fqcn'       => str_contains($content, '\\App\\Services\\UserService::create'),
                    'no_attribute'   => !str_contains($content, '#[LinksAndCovers'),
                    'no_placeholder' => !str_contains($content, '@@A'),
                ];
            })
            ->toMatchArray([
                'has_see_tag'    => true,
                'has_fqcn'       => true,
                'no_attribute'   => true,
                'no_placeholder' => true,
            ]);

        it('uses FQCN with leading backslash in @see tags')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $prodFile = $this->tempDir.'/FqcnService.php';
                file_put_contents($prodFile, <<<'PHP'
<?php
class FqcnService
{
    #[TestedBy('@@A')]
    public function method(): void {}
}
PHP);

                $prodEntry = new PlaceholderEntry(
                    '@@A', 'App\\Services\\FqcnService::method', $prodFile, 4, 'production', null, true
                );
                $testEntry = new PlaceholderEntry(
                    '@@A', 'Tests\\Unit\\FqcnServiceTest::testMethod', '/test.php', 10, 'test', 'phpunit', true
                );
                $action = new PlaceholderAction('@@A', $prodEntry, $testEntry);

                $modifier = new PlaceholderModifier();
                $modifier->apply([$action], dryRun: false);

                $content = file_get_contents($prodFile);

                // FQCN should start with backslash
                return str_contains($content, '@see \\Tests\\Unit\\FqcnServiceTest::testMethod');
            })
            ->toBeTrue();

        it('handles N:M placeholders with @@prefix for @see tags')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $prodFile = $this->tempDir.'/MultiSeeService.php';
                file_put_contents($prodFile, <<<'PHP'
<?php
class MultiSeeService
{
    #[TestedBy('@@A')]
    public function method(): void {}
}
PHP);

                $prodEntry = new PlaceholderEntry(
                    '@@A', 'App\\Services\\MultiSeeService::method', $prodFile, 4, 'production', null, true
                );

                // Two tests with same @@placeholder
                $testEntry1 = new PlaceholderEntry(
                    '@@A', 'Tests\\Unit\\Test1::testMethod1', '/test1.php', 10, 'test', 'phpunit', true
                );
                $testEntry2 = new PlaceholderEntry(
                    '@@A', 'Tests\\Unit\\Test2::testMethod2', '/test2.php', 20, 'test', 'phpunit', true
                );

                $action1 = new PlaceholderAction('@@A', $prodEntry, $testEntry1);
                $action2 = new PlaceholderAction('@@A', $prodEntry, $testEntry2);

                $modifier = new PlaceholderModifier();
                $modifier->apply([$action1, $action2], dryRun: false);

                $content = file_get_contents($prodFile);

                return [
                    'has_test1'      => str_contains($content, 'Test1::testMethod1'),
                    'has_test2'      => str_contains($content, 'Test2::testMethod2'),
                    'no_placeholder' => !str_contains($content, '@@A'),
                    'count_see_tags' => substr_count($content, '@see'),
                ];
            })
            ->toMatchArray([
                'has_test1'      => true,
                'has_test2'      => true,
                'no_placeholder' => true,
                'count_see_tags' => 2,
            ]);

        it('does not modify Pest test files for @@prefix (unsupported)')
            ->linksAndCovers(PlaceholderModifier::class.'::apply')
            ->expect(function () {
                $testFile        = $this->tempDir.'/PestSeeTest.php';
                $originalContent = <<<'PHP'
<?php
test('pest test', function () {
    // test
})->linksAndCovers('@@A');
PHP;
                file_put_contents($testFile, $originalContent);

                $prodEntry = new PlaceholderEntry(
                    '@@A', 'App\\Services\\UserService::create', '/prod.php', 4, 'production', null, true
                );
                $testEntry = new PlaceholderEntry(
                    '@@A', 'Tests\\PestSeeTest::pest test', $testFile, 4, 'test', 'pest', true
                );
                $action = new PlaceholderAction('@@A', $prodEntry, $testEntry);

                $modifier = new PlaceholderModifier();
                $modifier->apply([$action], dryRun: false);

                $content = file_get_contents($testFile);

                // Pest file should remain unchanged (@@+Pest is unsupported)
                return $content === $originalContent;
            })
            ->toBeTrue();
    });
});
