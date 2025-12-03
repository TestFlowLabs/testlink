<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Placeholder;

/**
 * Resolves placeholder pairs between production and test code.
 *
 * Creates N:M matches where each placeholder maps multiple production methods
 * to multiple tests. Reports errors for orphan placeholders.
 */
final class PlaceholderResolver
{
    /**
     * Resolve all placeholders in the registry.
     *
     * Creates actions for matched pairs and reports errors for orphans.
     */
    public function resolve(PlaceholderRegistry $registry): PlaceholderResult
    {
        $actions  = [];
        $errors   = [];
        $warnings = [];

        $placeholderIds = $registry->getAllPlaceholderIds();

        foreach ($placeholderIds as $placeholderId) {
            $productionEntries = $registry->getProductionEntries($placeholderId);
            $testEntries       = $registry->getTestEntries($placeholderId);

            // Check for orphan production placeholder
            if (count($productionEntries) > 0 && count($testEntries) === 0) {
                foreach ($productionEntries as $entry) {
                    $errors[] = sprintf(
                        'Placeholder %s found in %s::%s but no matching test',
                        $placeholderId,
                        $entry->getClassName(),
                        $entry->getMethodName() ?? 'unknown'
                    );
                }

                continue;
            }

            // Check for orphan test placeholder
            if (count($testEntries) > 0 && count($productionEntries) === 0) {
                foreach ($testEntries as $entry) {
                    $errors[] = sprintf(
                        'Placeholder %s found in test %s but no matching production method',
                        $placeholderId,
                        $entry->identifier
                    );
                }

                continue;
            }

            // Create N:M actions (all combinations)
            foreach ($productionEntries as $productionEntry) {
                foreach ($testEntries as $testEntry) {
                    $actions[] = new PlaceholderAction(
                        placeholderId: $placeholderId,
                        productionEntry: $productionEntry,
                        testEntry: $testEntry,
                    );
                }
            }
        }

        return new PlaceholderResult(
            actions: $actions,
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * Resolve a specific placeholder only.
     */
    public function resolvePlaceholder(string $placeholderId, PlaceholderRegistry $registry): PlaceholderResult
    {
        $actions  = [];
        $errors   = [];
        $warnings = [];

        // Validate placeholder format
        if (!PlaceholderRegistry::isPlaceholder($placeholderId)) {
            $errors[] = sprintf(
                'Invalid placeholder format: %s. Must start with @ followed by a letter.',
                $placeholderId
            );

            return new PlaceholderResult(
                actions: $actions,
                errors: $errors,
                warnings: $warnings,
            );
        }

        $productionEntries = $registry->getProductionEntries($placeholderId);
        $testEntries       = $registry->getTestEntries($placeholderId);

        // Check if placeholder exists
        if (count($productionEntries) === 0 && count($testEntries) === 0) {
            $errors[] = sprintf(
                'Placeholder %s not found in any production or test file',
                $placeholderId
            );

            return new PlaceholderResult(
                actions: $actions,
                errors: $errors,
                warnings: $warnings,
            );
        }

        // Check for orphan production placeholder
        if (count($productionEntries) > 0 && count($testEntries) === 0) {
            foreach ($productionEntries as $entry) {
                $errors[] = sprintf(
                    'Placeholder %s found in %s::%s but no matching test',
                    $placeholderId,
                    $entry->getClassName(),
                    $entry->getMethodName() ?? 'unknown'
                );
            }

            return new PlaceholderResult(
                actions: $actions,
                errors: $errors,
                warnings: $warnings,
            );
        }

        // Check for orphan test placeholder
        if (count($testEntries) > 0 && count($productionEntries) === 0) {
            foreach ($testEntries as $entry) {
                $errors[] = sprintf(
                    'Placeholder %s found in test %s but no matching production method',
                    $placeholderId,
                    $entry->identifier
                );
            }

            return new PlaceholderResult(
                actions: $actions,
                errors: $errors,
                warnings: $warnings,
            );
        }

        // Create N:M actions (all combinations)
        foreach ($productionEntries as $productionEntry) {
            foreach ($testEntries as $testEntry) {
                $actions[] = new PlaceholderAction(
                    placeholderId: $placeholderId,
                    productionEntry: $productionEntry,
                    testEntry: $testEntry,
                );
            }
        }

        return new PlaceholderResult(
            actions: $actions,
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * Get a summary of what the resolution would produce.
     *
     * @return array<string, array{production_count: int, test_count: int, link_count: int}>
     */
    public function getSummary(PlaceholderRegistry $registry): array
    {
        $summary = [];

        foreach ($registry->getAllPlaceholderIds() as $placeholderId) {
            $productionCount = count($registry->getProductionEntries($placeholderId));
            $testCount       = count($registry->getTestEntries($placeholderId));

            $summary[$placeholderId] = [
                'production_count' => $productionCount,
                'test_count'       => $testCount,
                'link_count'       => $productionCount * $testCount,
            ];
        }

        return $summary;
    }
}
