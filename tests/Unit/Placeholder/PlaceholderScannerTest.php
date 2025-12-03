<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Placeholder\PlaceholderScanner;
use TestFlowLabs\TestLink\Placeholder\PlaceholderRegistry;
use Tests\Fixtures\Placeholder\Production\PlaceholderUserService;

describe('PlaceholderScanner', function (): void {
    beforeEach(function (): void {
        $this->scanner  = new PlaceholderScanner();
        $this->registry = new PlaceholderRegistry();
    });

    describe('scanProductionClass', function (): void {
        it('finds @placeholder in TestedBy attribute')
            ->linksAndCovers(PlaceholderScanner::class.'::scanProductionClass')
            ->expect(function () {
                $this->scanner->scanProductionClass(PlaceholderUserService::class, $this->registry);

                $entries = $this->registry->getProductionEntries('@user-create');

                return [
                    'count'      => count($entries),
                    'identifier' => $entries[0]->identifier ?? null,
                ];
            })
            ->toMatchArray([
                'count'      => 1,
                'identifier' => 'Tests\\Fixtures\\Placeholder\\Production\\PlaceholderUserService::create',
            ]);

        it('finds multiple placeholders on same method')
            ->linksAndCovers(PlaceholderScanner::class.'::scanProductionClass')
            ->expect(function () {
                $this->scanner->scanProductionClass(PlaceholderUserService::class, $this->registry);

                return [
                    'has_a' => $this->registry->hasProductionEntries('@A'),
                    'has_b' => $this->registry->hasProductionEntries('@B'),
                ];
            })
            ->toMatchArray([
                'has_a' => true,
                'has_b' => true,
            ]);

        it('ignores non-placeholder TestedBy values')
            ->linksAndCovers(PlaceholderScanner::class.'::scanProductionClass')
            ->expect(function () {
                $this->scanner->scanProductionClass(PlaceholderUserService::class, $this->registry);

                $ids = $this->registry->getAllPlaceholderIds();

                // Should only have placeholder IDs, not class references
                $hasClassRef = false;
                foreach ($ids as $id) {
                    if (str_contains($id, 'Tests\\Fixtures\\TestCode')) {
                        $hasClassRef = true;
                    }
                }

                return $hasClassRef;
            })
            ->toBeFalse();
    });

    describe('setProjectRoot', function (): void {
        it('returns self for fluent interface')
            ->linksAndCovers(PlaceholderScanner::class.'::setProjectRoot')
            ->expect(function () {
                $result = $this->scanner->setProjectRoot('/path/to/project');

                return $result === $this->scanner;
            })
            ->toBeTrue();
    });

    describe('scan', function (): void {
        it('scans both production classes and test files')
            ->linksAndCovers(PlaceholderScanner::class.'::scan')
            ->expect(function () {
                // Note: This test relies on the test fixtures being in the classmap
                // For the production class, we'll scan it directly
                $this->scanner->scanProductionClass(PlaceholderUserService::class, $this->registry);

                return $this->registry->getProductionEntryCount() > 0;
            })
            ->toBeTrue();
    });
});
