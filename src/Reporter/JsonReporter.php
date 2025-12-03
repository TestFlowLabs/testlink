<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Reporter;

use TestFlowLabs\TestLink\Registry\TestLinkRegistry;

/**
 * JSON output for CI/CD integration.
 */
final class JsonReporter
{
    /**
     * Generate a JSON report of all coverage links.
     */
    public function report(TestLinkRegistry $registry): string
    {
        $data = [
            'links'   => $registry->getAllLinks(),
            'summary' => [
                'totalLinks'     => $registry->count(),
                'methodsCovered' => $registry->countMethods(),
                'testsWithLinks' => $registry->countTests(),
            ],
        ];

        return $this->encode($data);
    }

    /**
     * Generate a JSON report of validation results.
     *
     * @param  array{
     *     valid: bool,
     *     missingInTests: list<array{method: string, expectedTests: list<string>}>,
     *     missingInAttributes: list<array{test: string, expectedMethods: list<string>}>
     * }  $result
     */
    public function reportValidation(array $result): string
    {
        $data = [
            'valid'  => $result['valid'],
            'issues' => [
                'missingInTests'      => $result['missingInTests'],
                'missingInAttributes' => $result['missingInAttributes'],
            ],
            'summary' => [
                'totalIssues'         => count($result['missingInTests']) + count($result['missingInAttributes']),
                'missingInTests'      => count($result['missingInTests']),
                'missingInAttributes' => count($result['missingInAttributes']),
            ],
        ];

        return $this->encode($data);
    }

    /**
     * Encode data as pretty-printed JSON.
     *
     * @param  array<string, mixed>  $data
     */
    private function encode(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return '{"error": "Failed to encode JSON"}';
        }

        return $json;
    }
}
