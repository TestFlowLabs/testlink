<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\Discovery\TestCaseFinder;
use TestFlowLabs\TestLink\Sync\Exception\TestCaseNotFoundException;

describe('TestCaseFinder', function (): void {
    beforeEach(function (): void {
        $this->finder  = new TestCaseFinder();
        $this->testDir = sys_get_temp_dir().'/testlink-tests';
        @mkdir($this->testDir, 0777, true);
    });

    afterEach(function (): void {
        // Clean up temp files
        array_map('unlink', glob($this->testDir.'/*.php') ?: []);
        @rmdir($this->testDir);
    });

    describe('findTestCase', function (): void {
        it('finds test case by name', function (): void {
            $filePath = $this->testDir.'/SampleTest.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('creates a user', function () {
                    expect(true)->toBeTrue();
                });
                PHP);

            $result = $this->finder->findTestCase($filePath, 'creates a user');

            expect($result->name)->toBe('creates a user');
        });

        it('throws when file does not exist', function (): void {
            $this->finder->findTestCase('/non/existent/file.php', 'test');
        })->throws(TestCaseNotFoundException::class);

        it('throws when test case not found', function (): void {
            $filePath = $this->testDir.'/SampleTest.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('existing test', function () {});
                PHP);

            $this->finder->findTestCase($filePath, 'non-existing test');
        })->throws(TestCaseNotFoundException::class);
    });

    describe('findAllTestCases', function (): void {
        it('returns all test cases in file', function (): void {
            $filePath = $this->testDir.'/SampleTest.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('first test', function () {});
                test('second test', function () {});
                it('third test', function () {});
                PHP);

            $results = $this->finder->findAllTestCases($filePath);

            expect($results)->toHaveCount(3);
        });

        it('returns empty array for non-existent file', function (): void {
            $results = $this->finder->findAllTestCases('/non/existent.php');

            expect($results)->toBe([]);
        });

        it('returns empty array for file with no tests', function (): void {
            $filePath = $this->testDir.'/Helper.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                function helper() {
                    return 'hello';
                }
                PHP);

            $results = $this->finder->findAllTestCases($filePath);

            expect($results)->toBe([]);
        });
    });

    describe('testCaseExists', function (): void {
        it('returns true when test exists', function (): void {
            $filePath = $this->testDir.'/SampleTest.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('existing test', function () {});
                PHP);

            expect($this->finder->testCaseExists($filePath, 'existing test'))->toBeTrue();
        });

        it('returns false when test does not exist', function (): void {
            $filePath = $this->testDir.'/SampleTest.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('some test', function () {});
                PHP);

            expect($this->finder->testCaseExists($filePath, 'other test'))->toBeFalse();
        });
    });

    describe('findTestCasesMatching', function (): void {
        it('finds tests matching pattern', function (): void {
            $filePath = $this->testDir.'/SampleTest.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('creates a user', function () {});
                test('updates a user', function () {});
                test('deletes a record', function () {});
                PHP);

            $results = $this->finder->findTestCasesMatching($filePath, '*user*');

            expect($results)->toHaveCount(2);
        });

        it('returns empty array for no matches', function (): void {
            $filePath = $this->testDir.'/SampleTest.php';
            file_put_contents($filePath, <<<'PHP'
                <?php

                test('test one', function () {});
                PHP);

            $results = $this->finder->findTestCasesMatching($filePath, '*nonexistent*');

            expect($results)->toBe([]);
        });
    });
});
