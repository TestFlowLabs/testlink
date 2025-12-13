<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Scanner\PestLinkScanner;
use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

describe('PestLinkScanner', function (): void {
    beforeEach(function (): void {
        $this->scanner  = new PestLinkScanner();
        $this->testDir  = sys_get_temp_dir().'/testlink-pest-scanner-'.uniqid();

        @mkdir($this->testDir.'/tests/Unit', 0777, true);
    });

    afterEach(function (): void {
        // Clean up temp files recursively
        $files = glob($this->testDir.'/tests/Unit/*.php') ?: [];
        array_map('unlink', $files);

        @rmdir($this->testDir.'/tests/Unit');
        @rmdir($this->testDir.'/tests');

        $composerPath = $this->testDir.'/composer.json';

        if (file_exists($composerPath)) {
            unlink($composerPath);
        }

        @rmdir($this->testDir);
    });

    describe('namespaceFromPath', function (): void {
        it('uses namespace from composer.json autoload-dev psr-4', function (): void {
            // Create composer.json with custom namespace
            file_put_contents(
                $this->testDir.'/composer.json',
                json_encode([
                    'autoload-dev' => [
                        'psr-4' => [
                            'Acme\\App\\Tests\\' => 'tests',
                        ],
                    ],
                ])
            );

            // Create a Pest test file
            file_put_contents(
                $this->testDir.'/tests/Unit/ExampleTest.php',
                <<<'PHP'
<?php

test('example test', function () {
    expect(true)->toBeTrue();
})->linksAndCovers(SomeClass::class.'::method');
PHP
            );

            $this->scanner->setProjectRoot($this->testDir);
            $registry = new TestLinkRegistry();
            $this->scanner->scan($registry);

            $allLinks = $registry->getAllLinksByTest();

            // Should use Acme\App\Tests\ prefix, not Tests\
            expect(array_keys($allLinks)[0] ?? '')->toContain('Acme\\App\\Tests\\Unit\\ExampleTest');
        });

        it('falls back to Tests prefix when no autoload-dev', function (): void {
            // Create composer.json without autoload-dev
            file_put_contents(
                $this->testDir.'/composer.json',
                json_encode([
                    'name' => 'test/project',
                ])
            );

            // Create a Pest test file
            file_put_contents(
                $this->testDir.'/tests/Unit/ExampleTest.php',
                <<<'PHP'
<?php

test('example test', function () {
    expect(true)->toBeTrue();
})->linksAndCovers(SomeClass::class.'::method');
PHP
            );

            $this->scanner->setProjectRoot($this->testDir);
            $registry = new TestLinkRegistry();
            $this->scanner->scan($registry);

            $allLinks = $registry->getAllLinksByTest();

            // Should fall back to Tests\ prefix
            expect(array_keys($allLinks)[0] ?? '')->toContain('Tests\\Unit\\ExampleTest');
        });
    });
});
