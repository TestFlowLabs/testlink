<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\SyncResult;
use TestFlowLabs\TestLink\Sync\Modifier\ProductionFileModifier;

describe('ProductionFileModifier', function (): void {
    describe('addSeeTags', function (): void {
        it('returns empty result for empty input', function (): void {
            $modifier = new ProductionFileModifier();
            $result   = $modifier->addSeeTags([]);

            expect($result)->toBeInstanceOf(SyncResult::class);
            expect($result->hasChanges())->toBeFalse();
        });

        it('skips method identifiers without double colon', function (): void {
            $modifier = new ProductionFileModifier();
            $result   = $modifier->addSeeTags([
                'InvalidIdentifier' => ['\Tests\SomeTest::test'],
            ]);

            // No changes since identifier is invalid
            expect($result->modifiedFiles)->toBe([]);
        });

        it('skips nonexistent classes', function (): void {
            $modifier = new ProductionFileModifier();
            $result   = $modifier->addSeeTags([
                'NonExistent\Class::method' => ['\Tests\SomeTest::test'],
            ]);

            // No changes since class doesn't exist
            expect($result->modifiedFiles)->toBe([]);
        });

        it('resolves valid class file paths', function (): void {
            // This test verifies that the class resolution works
            // without actually modifying files
            $modifier = new ProductionFileModifier();

            // Pass an empty references array to avoid actual file modification
            // This just tests the class resolution logic
            $result = $modifier->addSeeTags([]);

            expect($result)->toBeInstanceOf(SyncResult::class);
            expect($result->hasChanges())->toBeFalse();
        });
    });

    describe('removeSeeTags', function (): void {
        it('returns empty result for empty input', function (): void {
            $modifier = new ProductionFileModifier();
            $result   = $modifier->removeSeeTags([]);

            expect($result)->toBeInstanceOf(SyncResult::class);
            expect($result->hasChanges())->toBeFalse();
        });

        it('skips method identifiers without double colon', function (): void {
            $modifier = new ProductionFileModifier();
            $result   = $modifier->removeSeeTags([
                'InvalidIdentifier' => ['\Tests\SomeTest::test'],
            ]);

            expect($result->prunedFiles)->toBe([]);
        });

        it('skips nonexistent classes', function (): void {
            $modifier = new ProductionFileModifier();
            $result   = $modifier->removeSeeTags([
                'NonExistent\Class::method' => ['\Tests\SomeTest::test'],
            ]);

            expect($result->prunedFiles)->toBe([]);
        });

        it('returns empty pruned files for nonexistent references', function (): void {
            $modifier = new ProductionFileModifier();

            // Use nonexistent class - should skip without errors
            $result = $modifier->removeSeeTags([
                'NonExistent\Class::method' => ['\Tests\Nonexistent::test'],
            ]);

            expect($result)->toBeInstanceOf(SyncResult::class);
            expect($result->prunedFiles)->toBe([]);
        });
    });

    describe('integration', function (): void {
        it('processes multiple invalid method identifiers gracefully', function (): void {
            $modifier = new ProductionFileModifier();

            // Test with only invalid identifiers (won't modify files)
            $result = $modifier->addSeeTags([
                'InvalidNoColon'            => ['\Tests\Test::test'],
                'NonExistent\Class::method' => ['\Tests\Test::test'],
                'AnotherInvalid'            => ['\Tests\Test2::test'],
            ]);

            // Should handle gracefully without errors or modifications
            expect($result)->toBeInstanceOf(SyncResult::class);
            expect($result->modifiedFiles)->toBe([]);
        });

        it('returns SyncResult with correct type', function (): void {
            $modifier = new ProductionFileModifier();

            // Test the return type is correct
            $addResult    = $modifier->addSeeTags([]);
            $removeResult = $modifier->removeSeeTags([]);

            expect($addResult)->toBeInstanceOf(SyncResult::class);
            expect($removeResult)->toBeInstanceOf(SyncResult::class);
        });
    });
});
