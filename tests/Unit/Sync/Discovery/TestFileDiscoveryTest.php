<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\Discovery\TestFileDiscovery;
use TestFlowLabs\TestLink\Sync\Exception\TestFileNotFoundException;

describe('TestFileDiscovery', function (): void {
    beforeEach(function (): void {
        $this->discovery = new TestFileDiscovery();
        $this->testDir   = sys_get_temp_dir().'/testlink-discovery';
        @mkdir($this->testDir.'/tests/Unit', 0777, true);
    });

    afterEach(function (): void {
        // Clean up temp files recursively
        $files = glob($this->testDir.'/tests/Unit/*.php') ?: [];
        array_map('unlink', $files);
        @rmdir($this->testDir.'/tests/Unit');
        @rmdir($this->testDir.'/tests');
        @rmdir($this->testDir);
    });

    describe('extractClassName', function (): void {
        it('extracts class name from test identifier', function (): void {
            $result = $this->discovery->extractClassName('Tests\\Unit\\UserServiceTest::test create user');

            expect($result)->toBe('Tests\\Unit\\UserServiceTest');
        });

        it('handles identifier without method', function (): void {
            $result = $this->discovery->extractClassName('Tests\\Unit\\UserServiceTest');

            expect($result)->toBe('Tests\\Unit\\UserServiceTest');
        });
    });

    describe('extractTestName', function (): void {
        it('extracts test name from identifier', function (): void {
            $result = $this->discovery->extractTestName('Tests\\Unit\\UserServiceTest::test create user');

            expect($result)->toBe('test create user');
        });

        it('returns empty string when no method', function (): void {
            $result = $this->discovery->extractTestName('Tests\\Unit\\UserServiceTest');

            expect($result)->toBe('');
        });
    });

    describe('testFileExists', function (): void {
        it('returns true when file can be found', function (): void {
            $this->discovery->setProjectRoot($this->testDir);

            // Create a test file
            file_put_contents(
                $this->testDir.'/tests/Unit/UserServiceTest.php',
                '<?php class UserServiceTest {}'
            );

            $result = $this->discovery->testFileExists('Tests\\Unit\\UserServiceTest::test');

            expect($result)->toBeTrue();
        });

        it('returns false when file cannot be found', function (): void {
            $this->discovery->setProjectRoot($this->testDir);

            $result = $this->discovery->testFileExists('Tests\\NonExistent\\SomeTest::test');

            expect($result)->toBeFalse();
        });
    });

    describe('findTestFile', function (): void {
        it('finds test file via PSR-4 path', function (): void {
            $this->discovery->setProjectRoot($this->testDir);

            // Create a test file
            file_put_contents(
                $this->testDir.'/tests/Unit/UserServiceTest.php',
                '<?php class UserServiceTest {}'
            );

            $result = $this->discovery->findTestFile('Tests\\Unit\\UserServiceTest::test');

            expect($result)->toContain('UserServiceTest.php');
        });

        it('throws when file not found', function (): void {
            $this->discovery->setProjectRoot($this->testDir);

            $this->discovery->findTestFile('Tests\\NonExistent\\SomeTest::test');
        })->throws(TestFileNotFoundException::class);
    });

    describe('setProjectRoot', function (): void {
        it('returns self for fluent interface', function (): void {
            $result = $this->discovery->setProjectRoot('/tmp');

            expect($result)->toBe($this->discovery);
        });
    });
});
