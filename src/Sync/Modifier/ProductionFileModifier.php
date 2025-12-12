<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Sync\Modifier;

use ReflectionClass;
use TestFlowLabs\TestLink\Sync\SyncResult;
use TestFlowLabs\TestLink\DocBlock\DocBlockModifier;

/**
 * Modifies production files to add @see tags.
 *
 * Adds @see tags to production method docblocks pointing to
 * their associated test methods.
 */
final class ProductionFileModifier
{
    public function __construct(
        private readonly DocBlockModifier $docBlockModifier = new DocBlockModifier(),
    ) {}

    /**
     * Add @see tags to production methods.
     *
     * @param  array<string, list<string>>  $seeTagsToAdd  methodIdentifier => list of test references
     */
    public function addSeeTags(array $seeTagsToAdd): SyncResult
    {
        if ($seeTagsToAdd === []) {
            return new SyncResult();
        }

        /** @var array<string, list<string>> $modifiedFiles */
        $modifiedFiles = [];

        /** @var list<string> $errors */
        $errors = [];

        // Group by file to minimize file reads/writes
        $groupedByFile = $this->groupByFile($seeTagsToAdd);

        foreach ($groupedByFile as $filePath => $methodReferences) {
            if (!file_exists($filePath)) {
                $errors[] = "File not found: {$filePath}";

                continue;
            }

            $code = file_get_contents($filePath);

            if ($code === false) {
                $errors[] = "Could not read file: {$filePath}";

                continue;
            }

            $modified     = false;
            $addedMethods = [];

            foreach ($methodReferences as $methodName => $references) {
                $result = $this->docBlockModifier->addSeeTags($code, $methodName, $references);

                if ($result['changed']) {
                    $code         = $result['code'];
                    $modified     = true;
                    $addedMethods = [...$addedMethods, ...array_map(
                        fn (string $ref): string => "@see {$ref}",
                        $references
                    )];
                }
            }

            if ($modified) {
                file_put_contents($filePath, $code);
                $modifiedFiles[$filePath] = array_values(array_unique($addedMethods));
            }
        }

        if ($errors !== []) {
            return SyncResult::withErrors($errors);
        }

        return SyncResult::applied($modifiedFiles);
    }

    /**
     * Remove @see tags from production methods.
     *
     * @param  array<string, list<string>>  $seeTagsToRemove  methodIdentifier => list of test references
     */
    public function removeSeeTags(array $seeTagsToRemove): SyncResult
    {
        if ($seeTagsToRemove === []) {
            return new SyncResult();
        }

        /** @var array<string, list<string>> $prunedFiles */
        $prunedFiles = [];

        // Group by file to minimize file reads/writes
        $groupedByFile = $this->groupByFile($seeTagsToRemove);

        foreach ($groupedByFile as $filePath => $methodReferences) {
            if (!file_exists($filePath)) {
                continue;
            }

            $code = file_get_contents($filePath);

            if ($code === false) {
                continue;
            }

            $modified       = false;
            $removedMethods = [];

            foreach ($methodReferences as $methodName => $references) {
                $result = $this->docBlockModifier->removeSeeTags($code, $methodName, $references);

                if ($result['changed']) {
                    $code           = $result['code'];
                    $modified       = true;
                    $removedMethods = [...$removedMethods, ...array_map(
                        fn (string $ref): string => "@see {$ref}",
                        $references
                    )];
                }
            }

            if ($modified) {
                file_put_contents($filePath, $code);
                $prunedFiles[$filePath] = array_values(array_unique($removedMethods));
            }
        }

        return SyncResult::applied([], $prunedFiles);
    }

    /**
     * Group method references by file path.
     *
     * @param  array<string, list<string>>  $seeTagsMap  methodIdentifier => list of references
     *
     * @return array<string, array<string, list<string>>> filePath => [methodName => references]
     */
    private function groupByFile(array $seeTagsMap): array
    {
        $grouped = [];

        foreach ($seeTagsMap as $methodIdentifier => $references) {
            [$className, $methodName] = $this->parseMethodIdentifier($methodIdentifier);
            if ($className === null) {
                continue;
            }
            if ($methodName === null) {
                continue;
            }

            $filePath = $this->resolveClassFilePath($className);

            if ($filePath === null) {
                continue;
            }

            $grouped[$filePath][$methodName] = $references;
        }

        return $grouped;
    }

    /**
     * Parse method identifier into class and method names.
     *
     * @return array{0: class-string|null, 1: ?string}
     */
    private function parseMethodIdentifier(string $methodIdentifier): array
    {
        if (!str_contains($methodIdentifier, '::')) {
            return [null, null];
        }

        $parts     = explode('::', $methodIdentifier, 2);
        $className = $parts[0];

        // Validate that it's actually a class that exists
        if (!class_exists($className)) {
            return [null, null];
        }

        /* @var class-string $className */
        return [$className, $parts[1]];
    }

    /**
     * Resolve a class name to its file path using reflection.
     *
     * @param  class-string  $className
     */
    private function resolveClassFilePath(string $className): ?string
    {
        try {
            $reflection = new ReflectionClass($className);
            $fileName   = $reflection->getFileName();

            return $fileName !== false ? $fileName : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
