<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Placeholder\PlaceholderRegistry;
use TestFlowLabs\TestLink\Placeholder\PlaceholderResolver;

describe('PlaceholderResolver', function (): void {
    describe('resolve', function (): void {
        it('creates action for matched 1:1 placeholder')
            ->linksAndCovers(PlaceholderResolver::class.'::resolve')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'UserService', 'create', '/prod.php', 10);
                $registry->registerTestPlaceholder('@A', 'UserServiceTest::test', '/test.php', 20, 'pest');

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolve($registry);

                return [
                    'action_count'   => count($result->actions),
                    'placeholder_id' => $result->actions[0]->placeholderId,
                    'has_errors'     => $result->hasErrors(),
                ];
            })
            ->toMatchArray([
                'action_count'   => 1,
                'placeholder_id' => '@A',
                'has_errors'     => false,
            ]);

        it('creates N*M actions for N:M placeholder (2 prod x 3 test = 6)')
            ->linksAndCovers(PlaceholderResolver::class.'::resolve')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                // 2 production methods
                $registry->registerProductionPlaceholder('@A', 'UserService', 'create', '/prod.php', 10);
                $registry->registerProductionPlaceholder('@A', 'UserService', 'update', '/prod.php', 20);
                // 3 tests
                $registry->registerTestPlaceholder('@A', 'Test1::test1', '/test1.php', 10, 'pest');
                $registry->registerTestPlaceholder('@A', 'Test2::test2', '/test2.php', 10, 'pest');
                $registry->registerTestPlaceholder('@A', 'Test3::test3', '/test3.php', 10, 'phpunit');

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolve($registry);

                return count($result->actions);
            })
            ->toBe(6);

        it('resolves multiple different placeholders')
            ->linksAndCovers(PlaceholderResolver::class.'::resolve')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'Service1', 'method1', '/s1.php', 10);
                $registry->registerTestPlaceholder('@A', 'Test1::t1', '/t1.php', 10, 'pest');
                $registry->registerProductionPlaceholder('@B', 'Service2', 'method2', '/s2.php', 10);
                $registry->registerTestPlaceholder('@B', 'Test2::t2', '/t2.php', 10, 'pest');

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolve($registry);

                $placeholderIds = array_map(fn ($a) => $a->placeholderId, $result->actions);

                return [
                    'count' => count($result->actions),
                    'has_a' => in_array('@A', $placeholderIds, true),
                    'has_b' => in_array('@B', $placeholderIds, true),
                ];
            })
            ->toMatchArray([
                'count' => 2,
                'has_a' => true,
                'has_b' => true,
            ]);

        it('returns error for production placeholder without matching test')
            ->linksAndCovers(PlaceholderResolver::class.'::resolve')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@orphan-prod', 'Service', 'method', '/s.php', 10);
                // No test with @orphan-prod

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolve($registry);

                return [
                    'has_errors'     => $result->hasErrors(),
                    'error_contains' => str_contains($result->errors[0], '@orphan-prod'),
                    'error_message'  => str_contains($result->errors[0], 'no matching test'),
                ];
            })
            ->toMatchArray([
                'has_errors'     => true,
                'error_contains' => true,
                'error_message'  => true,
            ]);

        it('returns error for test placeholder without matching production')
            ->linksAndCovers(PlaceholderResolver::class.'::resolve')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerTestPlaceholder('@orphan-test', 'Test::test', '/t.php', 10, 'pest');
                // No production with @orphan-test

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolve($registry);

                return [
                    'has_errors'     => $result->hasErrors(),
                    'error_contains' => str_contains($result->errors[0], '@orphan-test'),
                    'error_message'  => str_contains($result->errors[0], 'no matching production'),
                ];
            })
            ->toMatchArray([
                'has_errors'     => true,
                'error_contains' => true,
                'error_message'  => true,
            ]);

        it('returns multiple errors for multiple orphans')
            ->linksAndCovers(PlaceholderResolver::class.'::resolve')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@orphan1', 'S1', 'm1', '/s.php', 10);
                $registry->registerProductionPlaceholder('@orphan2', 'S2', 'm2', '/s.php', 20);

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolve($registry);

                return count($result->errors);
            })
            ->toBe(2);

        it('returns empty result for empty registry')
            ->linksAndCovers(PlaceholderResolver::class.'::resolve')
            ->expect(function () {
                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolve(new PlaceholderRegistry());

                return [
                    'actions' => $result->hasActions(),
                    'errors'  => $result->hasErrors(),
                ];
            })
            ->toMatchArray([
                'actions' => false,
                'errors'  => false,
            ]);

        it('returns error when @@prefix placeholder is used with Pest test')
            ->linksAndCovers(PlaceholderResolver::class.'::resolve')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@@A', 'UserService', 'create', '/prod.php', 10);
                $registry->registerTestPlaceholder('@@A', 'UserServiceTest::test', '/test.php', 20, 'pest');

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolve($registry);

                return [
                    'has_errors'        => $result->hasErrors(),
                    'has_actions'       => $result->hasActions(),
                    'error_contains_at' => str_contains($result->errors[0], '@@A'),
                    'error_mentions_see' => str_contains($result->errors[0], '@see'),
                    'error_mentions_pest' => str_contains($result->errors[0], 'Pest'),
                ];
            })
            ->toMatchArray([
                'has_errors'        => true,
                'has_actions'       => false,
                'error_contains_at' => true,
                'error_mentions_see' => true,
                'error_mentions_pest' => true,
            ]);

        it('creates action for @@prefix placeholder with PHPUnit test')
            ->linksAndCovers(PlaceholderResolver::class.'::resolve')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@@A', 'UserService', 'create', '/prod.php', 10);
                $registry->registerTestPlaceholder('@@A', 'UserServiceTest::testCreate', '/test.php', 20, 'phpunit');

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolve($registry);

                return [
                    'has_errors'         => $result->hasErrors(),
                    'action_count'       => count($result->actions),
                    'use_see_production' => $result->actions[0]->useSeeTagOnProduction(),
                    'use_see_test'       => $result->actions[0]->useSeeTagOnTest(),
                ];
            })
            ->toMatchArray([
                'has_errors'         => false,
                'action_count'       => 1,
                'use_see_production' => true,
                'use_see_test'       => true,
            ]);

        it('returns error for mixed Pest tests when @@prefix used')
            ->linksAndCovers(PlaceholderResolver::class.'::resolve')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@@A', 'UserService', 'create', '/prod.php', 10);
                $registry->registerTestPlaceholder('@@A', 'Test1::test1', '/test1.php', 10, 'phpunit');
                $registry->registerTestPlaceholder('@@A', 'Test2::test2', '/test2.php', 20, 'pest'); // This should cause error

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolve($registry);

                return $result->hasErrors();
            })
            ->toBeTrue();

        it('skips action creation when @@prefix Pest error occurs')
            ->linksAndCovers(PlaceholderResolver::class.'::resolve')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@@A', 'UserService', 'create', '/prod.php', 10);
                $registry->registerTestPlaceholder('@@A', 'Test::test', '/test.php', 10, 'pest');

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolve($registry);

                return count($result->actions);
            })
            ->toBe(0);
    });

    describe('resolvePlaceholder', function (): void {
        it('resolves only the specified placeholder')
            ->linksAndCovers(PlaceholderResolver::class.'::resolvePlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'S1', 'm1', '/s.php', 10);
                $registry->registerTestPlaceholder('@A', 'T1::t1', '/t.php', 10, 'pest');
                $registry->registerProductionPlaceholder('@B', 'S2', 'm2', '/s.php', 20);
                $registry->registerTestPlaceholder('@B', 'T2::t2', '/t.php', 20, 'pest');

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolvePlaceholder('@A', $registry);

                return [
                    'count'       => count($result->actions),
                    'placeholder' => $result->actions[0]->placeholderId,
                ];
            })
            ->toMatchArray([
                'count'       => 1,
                'placeholder' => '@A',
            ]);

        it('returns error if specified placeholder not found')
            ->linksAndCovers(PlaceholderResolver::class.'::resolvePlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolvePlaceholder('@nonexistent', $registry);

                return [
                    'has_errors'     => $result->hasErrors(),
                    'error_contains' => str_contains($result->errors[0], '@nonexistent'),
                ];
            })
            ->toMatchArray([
                'has_errors'     => true,
                'error_contains' => true,
            ]);

        it('returns error for invalid placeholder format')
            ->linksAndCovers(PlaceholderResolver::class.'::resolvePlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolvePlaceholder('@123invalid', $registry);

                return [
                    'has_errors'     => $result->hasErrors(),
                    'error_contains' => str_contains($result->errors[0], 'Invalid placeholder format'),
                ];
            })
            ->toMatchArray([
                'has_errors'     => true,
                'error_contains' => true,
            ]);

        it('returns error for orphan production placeholder')
            ->linksAndCovers(PlaceholderResolver::class.'::resolvePlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@orphan', 'Service', 'method', '/s.php', 10);

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolvePlaceholder('@orphan', $registry);

                return $result->hasErrors();
            })
            ->toBeTrue();

        it('returns error for orphan test placeholder')
            ->linksAndCovers(PlaceholderResolver::class.'::resolvePlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerTestPlaceholder('@orphan', 'Test::test', '/t.php', 10, 'pest');

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolvePlaceholder('@orphan', $registry);

                return $result->hasErrors();
            })
            ->toBeTrue();

        it('returns error when @@prefix placeholder used with Pest in resolvePlaceholder')
            ->linksAndCovers(PlaceholderResolver::class.'::resolvePlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@@A', 'UserService', 'create', '/prod.php', 10);
                $registry->registerTestPlaceholder('@@A', 'Test::test', '/test.php', 20, 'pest');

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolvePlaceholder('@@A', $registry);

                return [
                    'has_errors'  => $result->hasErrors(),
                    'has_actions' => $result->hasActions(),
                ];
            })
            ->toMatchArray([
                'has_errors'  => true,
                'has_actions' => false,
            ]);

        it('resolves @@prefix placeholder with PHPUnit in resolvePlaceholder')
            ->linksAndCovers(PlaceholderResolver::class.'::resolvePlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@@A', 'UserService', 'create', '/prod.php', 10);
                $registry->registerTestPlaceholder('@@A', 'UserServiceTest::testCreate', '/test.php', 20, 'phpunit');

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolvePlaceholder('@@A', $registry);

                return [
                    'has_errors'   => $result->hasErrors(),
                    'action_count' => count($result->actions),
                ];
            })
            ->toMatchArray([
                'has_errors'   => false,
                'action_count' => 1,
            ]);

        it('accepts @@prefix as valid placeholder format')
            ->linksAndCovers(PlaceholderResolver::class.'::resolvePlaceholder')
            ->expect(function () {
                $registry = new PlaceholderRegistry();

                $resolver = new PlaceholderResolver();
                $result   = $resolver->resolvePlaceholder('@@valid-name', $registry);

                // Should fail with "not found" not "invalid format"
                return str_contains($result->errors[0], 'not found');
            })
            ->toBeTrue();
    });

    describe('getSummary', function (): void {
        it('returns summary of placeholder counts')
            ->linksAndCovers(PlaceholderResolver::class.'::getSummary')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                // @A: 2 prod x 3 test = 6 links
                $registry->registerProductionPlaceholder('@A', 'S1', 'm1', '/s.php', 10);
                $registry->registerProductionPlaceholder('@A', 'S1', 'm2', '/s.php', 20);
                $registry->registerTestPlaceholder('@A', 'T1::t1', '/t.php', 10, 'pest');
                $registry->registerTestPlaceholder('@A', 'T1::t2', '/t.php', 20, 'pest');
                $registry->registerTestPlaceholder('@A', 'T1::t3', '/t.php', 30, 'pest');

                $resolver = new PlaceholderResolver();
                $summary  = $resolver->getSummary($registry);

                return $summary['@A'];
            })
            ->toMatchArray([
                'production_count' => 2,
                'test_count'       => 3,
                'link_count'       => 6,
            ]);

        it('returns summary for multiple placeholders')
            ->linksAndCovers(PlaceholderResolver::class.'::getSummary')
            ->expect(function () {
                $registry = new PlaceholderRegistry();
                $registry->registerProductionPlaceholder('@A', 'S1', 'm1', '/s.php', 10);
                $registry->registerTestPlaceholder('@A', 'T1::t1', '/t.php', 10, 'pest');
                $registry->registerProductionPlaceholder('@B', 'S2', 'm2', '/s.php', 20);
                $registry->registerTestPlaceholder('@B', 'T2::t2', '/t.php', 20, 'pest');
                $registry->registerTestPlaceholder('@B', 'T2::t3', '/t.php', 30, 'pest');

                $resolver = new PlaceholderResolver();
                $summary  = $resolver->getSummary($registry);

                return [
                    'a_links' => $summary['@A']['link_count'],
                    'b_links' => $summary['@B']['link_count'],
                ];
            })
            ->toMatchArray([
                'a_links' => 1,
                'b_links' => 2,
            ]);
    });
});
