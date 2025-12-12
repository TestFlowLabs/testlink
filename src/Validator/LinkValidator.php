<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Validator;

use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

/**
 * Validates coverage links bidirectionally.
 *
 * Performs validation in two directions:
 * 1. Test links (LinksAndCovers/Links) → Production code
 * 2. Production links (TestedBy) → Test code
 *
 * Strict sync validation ensures both sides are synchronized.
 */
final class LinkValidator
{
    /**
     * Validate coverage links between two registries.
     *
     * @param  TestLinkRegistry  $attributeRegistry  Links from PHPUnit #[LinksAndCovers]/#[Links] attributes
     * @param  TestLinkRegistry  $runtimeRegistry  Links from Pest linksAndCovers()/links() calls
     *
     * @return array{
     *     valid: bool,
     *     attributeLinks: array<string, list<string>>,
     *     runtimeLinks: array<string, list<string>>,
     *     duplicates: list<array{test: string, method: string}>,
     *     totalLinks: int
     * }
     */
    public function validate(TestLinkRegistry $attributeRegistry, TestLinkRegistry $runtimeRegistry): array
    {
        $attributeLinks = $attributeRegistry->getAllLinksByTest();
        $runtimeLinks   = $runtimeRegistry->getAllLinksByTest();
        $duplicates     = $this->findDuplicates($attributeRegistry, $runtimeRegistry);

        $totalLinks = $this->countTotalLinks($attributeRegistry) + $this->countTotalLinks($runtimeRegistry);

        return [
            'valid'          => $duplicates === [],
            'attributeLinks' => $attributeLinks,
            'runtimeLinks'   => $runtimeLinks,
            'duplicates'     => $duplicates,
            'totalLinks'     => $totalLinks,
        ];
    }

    /**
     * Find duplicate links (same test linking to same method in both registries).
     *
     * @return list<array{test: string, method: string}>
     */
    public function findDuplicates(
        TestLinkRegistry $attributeRegistry,
        TestLinkRegistry $runtimeRegistry,
    ): array {
        $duplicates = [];

        foreach ($attributeRegistry->getAllLinksByTest() as $test => $methods) {
            $runtimeMethods = $runtimeRegistry->getMethodsForTest($test);

            foreach ($methods as $method) {
                if (in_array($method, $runtimeMethods, true)) {
                    $duplicates[] = [
                        'test'   => $test,
                        'method' => $method,
                    ];
                }
            }
        }

        return $duplicates;
    }

    /**
     * Get all links from both registries combined, including TestedBy links.
     *
     * @return array<string, list<string>>
     */
    public function getAllLinks(
        TestLinkRegistry $attributeRegistry,
        TestLinkRegistry $runtimeRegistry,
    ): array {
        $allLinks = [];

        // Add attribute links (from #[LinksAndCovers]/#[Links] in test files)
        foreach ($attributeRegistry->getAllLinksByTest() as $test => $methods) {
            foreach ($methods as $method) {
                $allLinks[$method] ??= [];
                $allLinks[$method][] = $test;
            }
        }

        // Add runtime links (from Pest linksAndCovers()/links() calls)
        foreach ($runtimeRegistry->getAllLinksByTest() as $test => $methods) {
            foreach ($methods as $method) {
                if (!isset($allLinks[$method]) || !in_array($test, $allLinks[$method], true)) {
                    $allLinks[$method] ??= [];
                    $allLinks[$method][] = $test;
                }
            }
        }

        // Add TestedBy links (from #[TestedBy] in production files)
        foreach ($attributeRegistry->getTestedByLinks() as $method => $tests) {
            foreach ($tests as $test) {
                if (!isset($allLinks[$method]) || !in_array($test, $allLinks[$method], true)) {
                    $allLinks[$method] ??= [];
                    $allLinks[$method][] = $test;
                }
            }
        }

        return $allLinks;
    }

    /**
     * Check if all links are valid (no duplicates).
     */
    public function isValid(TestLinkRegistry $attributeRegistry, TestLinkRegistry $runtimeRegistry): bool
    {
        return $this->validate($attributeRegistry, $runtimeRegistry)['valid'];
    }

    /**
     * Validate bidirectional sync between test links and TestedBy attributes.
     *
     * This performs strict sync validation:
     * - Every test link must have a corresponding TestedBy on the production method
     * - Every TestedBy must have a corresponding test link
     *
     * @param  TestLinkRegistry  $testRegistry  Combined links from test files (attributes + runtime)
     *
     * @return array{
     *     valid: bool,
     *     testLinks: array<string, list<string>>,
     *     testedByLinks: array<string, list<string>>,
     *     missingTestedBy: list<array{method: string, test: string}>,
     *     orphanTestedBy: list<array{method: string, test: string}>,
     *     totalTestLinks: int,
     *     totalTestedByLinks: int
     * }
     */
    public function validateBidirectional(TestLinkRegistry $testRegistry): array
    {
        $testLinks     = $testRegistry->getAllLinks();  // method -> tests (from test files)
        $testedByLinks = $testRegistry->getTestedByLinks();  // method -> tests (from TestedBy)

        $missingTestedBy = $this->findMissingTestedBy($testLinks, $testedByLinks);
        $orphanTestedBy  = $this->findOrphanTestedBy($testLinks, $testedByLinks);

        $totalTestLinks     = $this->countLinkEntries($testLinks);
        $totalTestedByLinks = $this->countLinkEntries($testedByLinks);

        $valid = $missingTestedBy === [] && $orphanTestedBy === [];

        return [
            'valid'              => $valid,
            'testLinks'          => $testLinks,
            'testedByLinks'      => $testedByLinks,
            'missingTestedBy'    => $missingTestedBy,
            'orphanTestedBy'     => $orphanTestedBy,
            'totalTestLinks'     => $totalTestLinks,
            'totalTestedByLinks' => $totalTestedByLinks,
        ];
    }

    /**
     * Find test links that don't have corresponding TestedBy attributes.
     *
     * @param  array<string, list<string>>  $testLinks
     * @param  array<string, list<string>>  $testedByLinks
     *
     * @return list<array{method: string, test: string}>
     */
    private function findMissingTestedBy(array $testLinks, array $testedByLinks): array
    {
        $missing = [];

        foreach ($testLinks as $method => $tests) {
            $testedByTests = $testedByLinks[$method] ?? [];

            foreach ($tests as $test) {
                if (!in_array($test, $testedByTests, true)) {
                    $missing[] = [
                        'method' => $method,
                        'test'   => $test,
                    ];
                }
            }
        }

        return $missing;
    }

    /**
     * Find TestedBy attributes that don't have corresponding test links.
     *
     * @param  array<string, list<string>>  $testLinks
     * @param  array<string, list<string>>  $testedByLinks
     *
     * @return list<array{method: string, test: string}>
     */
    private function findOrphanTestedBy(array $testLinks, array $testedByLinks): array
    {
        $orphan = [];

        foreach ($testedByLinks as $method => $tests) {
            $actualTests = $testLinks[$method] ?? [];

            foreach ($tests as $test) {
                if (!in_array($test, $actualTests, true)) {
                    $orphan[] = [
                        'method' => $method,
                        'test'   => $test,
                    ];
                }
            }
        }

        return $orphan;
    }

    /**
     * Check if bidirectional links are valid (strict sync).
     */
    public function isBidirectionalValid(TestLinkRegistry $testRegistry): bool
    {
        return $this->validateBidirectional($testRegistry)['valid'];
    }

    /**
     * Count total links in a registry.
     */
    private function countTotalLinks(TestLinkRegistry $registry): int
    {
        $count = 0;

        foreach ($registry->getAllLinksByTest() as $methods) {
            $count += count($methods);
        }

        return $count;
    }

    /**
     * Count total entries in a links array.
     *
     * @param  array<string, list<string>>  $links
     */
    private function countLinkEntries(array $links): int
    {
        $count = 0;

        foreach ($links as $items) {
            $count += count($items);
        }

        return $count;
    }
}
