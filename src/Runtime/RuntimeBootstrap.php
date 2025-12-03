<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Runtime;

use TestFlowLabs\TestLink\Discovery\FrameworkDetector;

/**
 * Bootstraps TestLink runtime for collecting coverage data.
 *
 * This should be called in:
 * - Pest: tests/Pest.php
 * - PHPUnit: phpunit.xml bootstrap or tests/bootstrap.php
 */
final class RuntimeBootstrap
{
    private static bool $initialized            = false;
    private static ?FrameworkDetector $detector = null;

    /**
     * Initialize TestLink runtime.
     *
     * Detects available frameworks and registers runtime hooks accordingly.
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$detector = new FrameworkDetector();

        // Register Pest runtime if available
        if (self::$detector->isPestAvailable()) {
            self::registerPestRuntime();
        }

        // PHPUnit uses attributes, no runtime registration needed
        // The scanner reads #[Links] and #[LinksAndCovers] via reflection

        self::$initialized = true;
    }

    /**
     * Check if runtime has been initialized.
     */
    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    /**
     * Get the framework detector instance.
     */
    public static function getDetector(): ?FrameworkDetector
    {
        return self::$detector;
    }

    /**
     * Reset the runtime (mainly for testing).
     */
    public static function reset(): void
    {
        self::$initialized = false;
        self::$detector    = null;
    }

    /**
     * Register Pest runtime hooks.
     *
     * Makes links() and linksAndCovers() available in Pest tests.
     */
    private static function registerPestRuntime(): void
    {
        if (!class_exists(\Pest\Plugin::class)) {
            return;
        }

        // Register the trait with Pest's plugin system
        \Pest\Plugin::uses(TestLinkTrait::class);
    }
}
