<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\Exception\TestCaseNotFoundException;

describe('TestCaseNotFoundException', function (): void {
    it('creates exception with test and file info', function (): void {
        $exception = new TestCaseNotFoundException('test name', '/path/to/file.php');

        expect($exception)->toBeInstanceOf(RuntimeException::class);
        expect($exception->getMessage())->toContain('test name');
        expect($exception->getMessage())->toContain('/path/to/file.php');
    });

    it('includes custom reason if provided', function (): void {
        $exception = new TestCaseNotFoundException('test name', '/path/file.php', 'File is empty');

        expect($exception->getMessage())->toContain('File is empty');
    });

    it('provides default message without reason', function (): void {
        $exception = new TestCaseNotFoundException('my test', '/tests/MyTest.php');

        expect($exception->getMessage())->toContain('my test');
        expect($exception->getMessage())->toContain('/tests/MyTest.php');
    });
});
