<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\DocBlock\SeeTagEntry;
use TestFlowLabs\TestLink\DocBlock\SeeTagRegistry;

describe('SeeTagRegistry', function (): void {
    describe('registerProductionSee', function (): void {
        it('registers a production @see entry', function (): void {
            $registry = new SeeTagRegistry();
            $entry    = new SeeTagEntry(
                reference: '\Tests\UserServiceTest::testCreate',
                filePath: '/app/src/Services/UserService.php',
                line: 42,
                context: 'production',
                methodName: 'create',
                className: 'App\Services\UserService',
            );

            $registry->registerProductionSee('App\Services\UserService::create', $entry);

            expect($registry->getProductionSeeFor('App\Services\UserService::create'))->toHaveCount(1);
            expect($registry->getProductionSeeFor('App\Services\UserService::create')[0])->toBe($entry);
        });

        it('allows multiple entries for same method', function (): void {
            $registry = new SeeTagRegistry();
            $entry1   = new SeeTagEntry('\Tests\Test1::test1', '/app/src/Foo.php', 10, 'production');
            $entry2   = new SeeTagEntry('\Tests\Test2::test2', '/app/src/Foo.php', 11, 'production');

            $registry->registerProductionSee('App\Foo::bar', $entry1);
            $registry->registerProductionSee('App\Foo::bar', $entry2);

            expect($registry->getProductionSeeFor('App\Foo::bar'))->toHaveCount(2);
        });
    });

    describe('registerTestSee', function (): void {
        it('registers a test @see entry', function (): void {
            $registry = new SeeTagRegistry();
            $entry    = new SeeTagEntry(
                reference: '\App\Services\UserService::create',
                filePath: '/app/tests/Unit/UserServiceTest.php',
                line: 25,
                context: 'test',
            );

            $registry->registerTestSee('Tests\UserServiceTest::testCreate', $entry);

            expect($registry->getTestSeeFor('Tests\UserServiceTest::testCreate'))->toHaveCount(1);
        });

        it('allows multiple entries for same test', function (): void {
            $registry = new SeeTagRegistry();
            $entry1   = new SeeTagEntry('\App\Foo::bar', '/app/tests/FooTest.php', 10, 'test');
            $entry2   = new SeeTagEntry('\App\Foo::baz', '/app/tests/FooTest.php', 11, 'test');

            $registry->registerTestSee('Tests\FooTest::testSomething', $entry1);
            $registry->registerTestSee('Tests\FooTest::testSomething', $entry2);

            expect($registry->getTestSeeFor('Tests\FooTest::testSomething'))->toHaveCount(2);
        });
    });

    describe('getProductionSeeFor', function (): void {
        it('returns empty array for unknown method', function (): void {
            $registry = new SeeTagRegistry();

            expect($registry->getProductionSeeFor('Unknown::method'))->toBe([]);
        });
    });

    describe('getTestSeeFor', function (): void {
        it('returns empty array for unknown test', function (): void {
            $registry = new SeeTagRegistry();

            expect($registry->getTestSeeFor('Unknown::test'))->toBe([]);
        });
    });

    describe('hasProductionSee', function (): void {
        it('returns true when production method has matching @see', function (): void {
            $registry = new SeeTagRegistry();
            $entry    = new SeeTagEntry('\Tests\FooTest::testBar', '/app/src/Foo.php', 10, 'production');
            $registry->registerProductionSee('App\Foo::bar', $entry);

            expect($registry->hasProductionSee('App\Foo::bar', '\Tests\FooTest::testBar'))->toBeTrue();
        });

        it('returns true with normalized reference (strips backslash)', function (): void {
            $registry = new SeeTagRegistry();
            $entry    = new SeeTagEntry('\Tests\FooTest::testBar', '/app/src/Foo.php', 10, 'production');
            $registry->registerProductionSee('App\Foo::bar', $entry);

            expect($registry->hasProductionSee('App\Foo::bar', 'Tests\FooTest::testBar'))->toBeTrue();
        });

        it('returns false when method has no @see tags', function (): void {
            $registry = new SeeTagRegistry();

            expect($registry->hasProductionSee('App\Foo::bar', '\Tests\FooTest::testBar'))->toBeFalse();
        });

        it('returns false when reference does not match', function (): void {
            $registry = new SeeTagRegistry();
            $entry    = new SeeTagEntry('\Tests\FooTest::testBar', '/app/src/Foo.php', 10, 'production');
            $registry->registerProductionSee('App\Foo::bar', $entry);

            expect($registry->hasProductionSee('App\Foo::bar', '\Tests\OtherTest::testOther'))->toBeFalse();
        });
    });

    describe('hasTestSee', function (): void {
        it('returns true when test has matching @see', function (): void {
            $registry = new SeeTagRegistry();
            $entry    = new SeeTagEntry('\App\Foo::bar', '/app/tests/FooTest.php', 10, 'test');
            $registry->registerTestSee('Tests\FooTest::testBar', $entry);

            expect($registry->hasTestSee('Tests\FooTest::testBar', '\App\Foo::bar'))->toBeTrue();
        });

        it('returns true with normalized reference', function (): void {
            $registry = new SeeTagRegistry();
            $entry    = new SeeTagEntry('\App\Foo::bar', '/app/tests/FooTest.php', 10, 'test');
            $registry->registerTestSee('Tests\FooTest::testBar', $entry);

            expect($registry->hasTestSee('Tests\FooTest::testBar', 'App\Foo::bar'))->toBeTrue();
        });

        it('returns false when test has no @see tags', function (): void {
            $registry = new SeeTagRegistry();

            expect($registry->hasTestSee('Tests\FooTest::testBar', '\App\Foo::bar'))->toBeFalse();
        });
    });

    describe('getAllProductionSeeTags', function (): void {
        it('returns all production @see tags grouped by method', function (): void {
            $registry = new SeeTagRegistry();
            $entry1   = new SeeTagEntry('\Tests\Test1::test1', '/app/src/Foo.php', 10, 'production');
            $entry2   = new SeeTagEntry('\Tests\Test2::test2', '/app/src/Bar.php', 20, 'production');

            $registry->registerProductionSee('App\Foo::method1', $entry1);
            $registry->registerProductionSee('App\Bar::method2', $entry2);

            $all = $registry->getAllProductionSeeTags();

            expect($all)->toHaveCount(2);
            expect($all)->toHaveKey('App\Foo::method1');
            expect($all)->toHaveKey('App\Bar::method2');
        });

        it('returns empty array when no entries', function (): void {
            $registry = new SeeTagRegistry();

            expect($registry->getAllProductionSeeTags())->toBe([]);
        });
    });

    describe('getAllTestSeeTags', function (): void {
        it('returns all test @see tags grouped by test', function (): void {
            $registry = new SeeTagRegistry();
            $entry1   = new SeeTagEntry('\App\Foo::bar', '/app/tests/FooTest.php', 10, 'test');
            $entry2   = new SeeTagEntry('\App\Bar::baz', '/app/tests/BarTest.php', 20, 'test');

            $registry->registerTestSee('Tests\FooTest::testFoo', $entry1);
            $registry->registerTestSee('Tests\BarTest::testBar', $entry2);

            $all = $registry->getAllTestSeeTags();

            expect($all)->toHaveCount(2);
            expect($all)->toHaveKey('Tests\FooTest::testFoo');
            expect($all)->toHaveKey('Tests\BarTest::testBar');
        });
    });

    describe('getAllEntries', function (): void {
        it('returns all entries from both production and test', function (): void {
            $registry  = new SeeTagRegistry();
            $prodEntry = new SeeTagEntry('\Tests\Test::test', '/app/src/Foo.php', 10, 'production');
            $testEntry = new SeeTagEntry('\App\Foo::bar', '/app/tests/FooTest.php', 20, 'test');

            $registry->registerProductionSee('App\Foo::bar', $prodEntry);
            $registry->registerTestSee('Tests\FooTest::testFoo', $testEntry);

            $all = $registry->getAllEntries();

            expect($all)->toHaveCount(2);
            expect($all)->toContain($prodEntry);
            expect($all)->toContain($testEntry);
        });

        it('returns empty array when no entries', function (): void {
            $registry = new SeeTagRegistry();

            expect($registry->getAllEntries())->toBe([]);
        });
    });

    describe('findOrphans', function (): void {
        it('returns production entries pointing to invalid tests', function (): void {
            $registry = new SeeTagRegistry();
            $entry    = new SeeTagEntry('\Tests\DeletedTest::testGone', '/app/src/Foo.php', 10, 'production');
            $registry->registerProductionSee('App\Foo::bar', $entry);

            $validTests = ['\Tests\FooTest::testFoo'];
            $orphans    = $registry->findOrphans([], $validTests);

            expect($orphans)->toHaveCount(1);
            expect($orphans[0])->toBe($entry);
        });

        it('returns test entries pointing to invalid production methods', function (): void {
            $registry = new SeeTagRegistry();
            $entry    = new SeeTagEntry('\App\Deleted::method', '/app/tests/FooTest.php', 10, 'test');
            $registry->registerTestSee('Tests\FooTest::testFoo', $entry);

            $validMethods = ['\App\Foo::bar'];
            $orphans      = $registry->findOrphans($validMethods, []);

            expect($orphans)->toHaveCount(1);
            expect($orphans[0])->toBe($entry);
        });

        it('does not return valid entries', function (): void {
            $registry  = new SeeTagRegistry();
            $prodEntry = new SeeTagEntry('\Tests\FooTest::testFoo', '/app/src/Foo.php', 10, 'production');
            $testEntry = new SeeTagEntry('\App\Foo::bar', '/app/tests/FooTest.php', 20, 'test');

            $registry->registerProductionSee('App\Foo::bar', $prodEntry);
            $registry->registerTestSee('Tests\FooTest::testFoo', $testEntry);

            $orphans = $registry->findOrphans(['\App\Foo::bar'], ['\Tests\FooTest::testFoo']);

            expect($orphans)->toBe([]);
        });

        it('returns orphans from both production and test', function (): void {
            $registry  = new SeeTagRegistry();
            $prodEntry = new SeeTagEntry('\Tests\DeletedTest::test', '/app/src/Foo.php', 10, 'production');
            $testEntry = new SeeTagEntry('\App\Deleted::method', '/app/tests/FooTest.php', 20, 'test');

            $registry->registerProductionSee('App\Foo::bar', $prodEntry);
            $registry->registerTestSee('Tests\FooTest::testFoo', $testEntry);

            $orphans = $registry->findOrphans([], []);

            expect($orphans)->toHaveCount(2);
        });
    });

    describe('count', function (): void {
        it('returns total count of all @see tags', function (): void {
            $registry = new SeeTagRegistry();
            $registry->registerProductionSee('App\Foo::bar', new SeeTagEntry('\Tests\T1::t1', '/f.php', 1, 'production'));
            $registry->registerProductionSee('App\Foo::bar', new SeeTagEntry('\Tests\T2::t2', '/f.php', 2, 'production'));
            $registry->registerTestSee('Tests\FooTest::test', new SeeTagEntry('\App\Foo::bar', '/t.php', 1, 'test'));

            expect($registry->count())->toBe(3);
        });

        it('returns 0 when empty', function (): void {
            $registry = new SeeTagRegistry();

            expect($registry->count())->toBe(0);
        });
    });

    describe('countProduction', function (): void {
        it('returns count of production @see tags only', function (): void {
            $registry = new SeeTagRegistry();
            $registry->registerProductionSee('App\Foo::bar', new SeeTagEntry('\Tests\T1::t1', '/f.php', 1, 'production'));
            $registry->registerProductionSee('App\Bar::baz', new SeeTagEntry('\Tests\T2::t2', '/b.php', 2, 'production'));
            $registry->registerTestSee('Tests\FooTest::test', new SeeTagEntry('\App\Foo::bar', '/t.php', 1, 'test'));

            expect($registry->countProduction())->toBe(2);
        });
    });

    describe('countTest', function (): void {
        it('returns count of test @see tags only', function (): void {
            $registry = new SeeTagRegistry();
            $registry->registerProductionSee('App\Foo::bar', new SeeTagEntry('\Tests\T1::t1', '/f.php', 1, 'production'));
            $registry->registerTestSee('Tests\FooTest::test1', new SeeTagEntry('\App\Foo::bar', '/t.php', 1, 'test'));
            $registry->registerTestSee('Tests\FooTest::test2', new SeeTagEntry('\App\Bar::baz', '/t.php', 2, 'test'));

            expect($registry->countTest())->toBe(2);
        });
    });

    describe('clear', function (): void {
        it('removes all registered entries', function (): void {
            $registry = new SeeTagRegistry();
            $registry->registerProductionSee('App\Foo::bar', new SeeTagEntry('\Tests\T1::t1', '/f.php', 1, 'production'));
            $registry->registerTestSee('Tests\FooTest::test', new SeeTagEntry('\App\Foo::bar', '/t.php', 1, 'test'));

            $registry->clear();

            expect($registry->count())->toBe(0);
            expect($registry->getAllProductionSeeTags())->toBe([]);
            expect($registry->getAllTestSeeTags())->toBe([]);
        });
    });
});
