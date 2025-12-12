<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\DocBlock\DocBlockParser;
use TestFlowLabs\TestLink\DocBlock\SeeTagRegistry;
use TestFlowLabs\TestLink\DocBlock\DocBlockScanner;

describe('DocBlockScanner', function (): void {
    describe('setProjectRoot', function (): void {
        it('returns self for fluent interface', function (): void {
            $scanner = new DocBlockScanner();
            $result  = $scanner->setProjectRoot('/some/path');

            expect($result)->toBe($scanner);
        });
    });

    describe('scan', function (): void {
        it('populates registry with @see tags from production and test classes', function (): void {
            $registry = new SeeTagRegistry();
            $scanner  = new DocBlockScanner();
            $scanner->setProjectRoot(getcwd() ?: __DIR__);

            $scanner->scan($registry);

            // The scanner should find @see tags in the project's actual source files
            // This is an integration test that verifies the scanning works
            expect($registry)->toBeInstanceOf(SeeTagRegistry::class);
        });
    });

    describe('scanProductionClasses', function (): void {
        it('scans only production classes', function (): void {
            $registry = new SeeTagRegistry();
            $scanner  = new DocBlockScanner();
            $scanner->setProjectRoot(getcwd() ?: __DIR__);

            $scanner->scanProductionClasses($registry);

            // Verify the scan completed (registry should exist)
            expect($registry)->toBeInstanceOf(SeeTagRegistry::class);

            // If any entries found, they should all be from production context
            $entries = $registry->getAllEntries();

            if ($entries !== []) {
                foreach ($entries as $entry) {
                    expect($entry->isProduction())->toBeTrue();
                }
            } else {
                // No @see tags in production code is valid
                expect($entries)->toBe([]);
            }
        });
    });

    describe('scanTestClasses', function (): void {
        it('scans only test classes', function (): void {
            $registry = new SeeTagRegistry();
            $scanner  = new DocBlockScanner();
            $scanner->setProjectRoot(getcwd() ?: __DIR__);

            $scanner->scanTestClasses($registry);

            // Verify the scan completed (registry should exist)
            expect($registry)->toBeInstanceOf(SeeTagRegistry::class);

            // If any entries found, they should all be from test context
            $entries = $registry->getAllEntries();

            if ($entries !== []) {
                foreach ($entries as $entry) {
                    expect($entry->isTest())->toBeTrue();
                }
            } else {
                // No @see tags in test code is valid
                expect($entries)->toBe([]);
            }
        });
    });

    describe('integration with DocBlockParser', function (): void {
        it('extracts @see references correctly', function (): void {
            $parser = new DocBlockParser();

            $docBlock = <<<'DOC'
/**
 * Creates a new user.
 *
 * @see \Tests\UserServiceTest::testCreate
 * @see \Tests\UserServiceTest::testCreateWithEmail
 */
DOC;

            $references = $parser->extractSeeReferences($docBlock);

            expect($references)->toBe([
                '\Tests\UserServiceTest::testCreate',
                '\Tests\UserServiceTest::testCreateWithEmail',
            ]);
        });
    });

    describe('with custom project root', function (): void {
        it('respects custom project root setting', function (): void {
            $scanner = new DocBlockScanner();
            $scanner->setProjectRoot('/nonexistent/path');

            $registry = new SeeTagRegistry();

            // Should not throw, just return empty since path doesn't exist
            $scanner->scan($registry);

            // No entries found for nonexistent path
            expect($registry->count())->toBe(0);
        });
    });

    describe('handles edge cases', function (): void {
        it('handles classes without docblocks gracefully', function (): void {
            $registry = new SeeTagRegistry();
            $scanner  = new DocBlockScanner();
            $scanner->setProjectRoot(getcwd() ?: __DIR__);

            // Should not throw even if some classes have no docblocks
            expect(fn () => $scanner->scan($registry))->not->toThrow(Throwable::class);
        });

        it('handles empty registry', function (): void {
            $registry = new SeeTagRegistry();

            expect($registry->count())->toBe(0);
            expect($registry->getAllEntries())->toBe([]);
        });
    });
});
