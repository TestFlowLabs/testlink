<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Discovery;

use Composer\InstalledVersions;

/**
 * Detects which testing frameworks are available in the project.
 */
final class FrameworkDetector
{
    public const FRAMEWORK_PEST = 'pest';

    public const FRAMEWORK_PHPUNIT = 'phpunit';

    /**
     * Detect all available testing frameworks.
     *
     * @return list<string> Framework names ('pest', 'phpunit')
     */
    public function detect(): array
    {
        $frameworks = [];

        if ($this->isPestAvailable()) {
            $frameworks[] = self::FRAMEWORK_PEST;
        }

        if ($this->isPhpUnitAvailable()) {
            $frameworks[] = self::FRAMEWORK_PHPUNIT;
        }

        return $frameworks;
    }

    /**
     * Check if Pest is installed.
     */
    public function isPestAvailable(): bool
    {
        return class_exists(\Pest\Plugin::class)
            || $this->isPackageInstalled('pestphp/pest');
    }

    /**
     * Check if PHPUnit is installed.
     */
    public function isPhpUnitAvailable(): bool
    {
        return class_exists(\PHPUnit\Framework\TestCase::class)
            || $this->isPackageInstalled('phpunit/phpunit');
    }

    /**
     * Get the primary framework.
     *
     * Prefers Pest if both are installed (since Pest runs on top of PHPUnit).
     */
    public function getPrimaryFramework(): ?string
    {
        $frameworks = $this->detect();

        if (in_array(self::FRAMEWORK_PEST, $frameworks, true)) {
            return self::FRAMEWORK_PEST;
        }

        if (in_array(self::FRAMEWORK_PHPUNIT, $frameworks, true)) {
            return self::FRAMEWORK_PHPUNIT;
        }

        return null;
    }

    /**
     * Check if multiple frameworks are available.
     */
    public function hasMultipleFrameworks(): bool
    {
        return count($this->detect()) > 1;
    }

    /**
     * Check if a specific package is installed via Composer.
     */
    private function isPackageInstalled(string $packageName): bool
    {
        if (!class_exists(InstalledVersions::class)) {
            return false;
        }

        try {
            return InstalledVersions::isInstalled($packageName);
        } catch (\Throwable) {
            return false;
        }
    }
}
