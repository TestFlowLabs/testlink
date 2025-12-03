<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Placeholder\PlaceholderEntry;
use TestFlowLabs\TestLink\Placeholder\PlaceholderAction;
use TestFlowLabs\TestLink\Placeholder\PlaceholderResult;

describe('PlaceholderResult', function (): void {
    beforeEach(function (): void {
        $this->createAction = function (string $placeholderId, string $prodFile, string $testFile): PlaceholderAction {
            $prodEntry = new PlaceholderEntry(
                $placeholderId, 'Class::method', $prodFile, 1, 'production', null
            );
            $testEntry = new PlaceholderEntry(
                $placeholderId, 'Test::test', $testFile, 1, 'test', 'pest'
            );

            return new PlaceholderAction($placeholderId, $prodEntry, $testEntry);
        };
    });

    describe('constructor', function (): void {
        it('creates empty result by default')
            ->expect(function () {
                $result = new PlaceholderResult();

                return [
                    'actions'  => $result->actions,
                    'errors'   => $result->errors,
                    'warnings' => $result->warnings,
                ];
            })
            ->toMatchArray([
                'actions'  => [],
                'errors'   => [],
                'warnings' => [],
            ]);
    });

    describe('hasErrors', function (): void {
        it('returns false when no errors')
            ->linksAndCovers(PlaceholderResult::class.'::hasErrors')
            ->expect(fn () => (new PlaceholderResult())->hasErrors())
            ->toBeFalse();

        it('returns true when has errors')
            ->linksAndCovers(PlaceholderResult::class.'::hasErrors')
            ->expect(fn () => (new PlaceholderResult(errors: ['Error 1']))->hasErrors())
            ->toBeTrue();
    });

    describe('hasWarnings', function (): void {
        it('returns false when no warnings')
            ->linksAndCovers(PlaceholderResult::class.'::hasWarnings')
            ->expect(fn () => (new PlaceholderResult())->hasWarnings())
            ->toBeFalse();

        it('returns true when has warnings')
            ->linksAndCovers(PlaceholderResult::class.'::hasWarnings')
            ->expect(fn () => (new PlaceholderResult(warnings: ['Warning 1']))->hasWarnings())
            ->toBeTrue();
    });

    describe('hasActions', function (): void {
        it('returns false when no actions')
            ->linksAndCovers(PlaceholderResult::class.'::hasActions')
            ->expect(fn () => (new PlaceholderResult())->hasActions())
            ->toBeFalse();

        it('returns true when has actions')
            ->linksAndCovers(PlaceholderResult::class.'::hasActions')
            ->expect(function () {
                $action = ($this->createAction)('@A', '/prod.php', '/test.php');

                return (new PlaceholderResult(actions: [$action]))->hasActions();
            })
            ->toBeTrue();
    });

    describe('getActionCount', function (): void {
        it('returns count of actions')
            ->linksAndCovers(PlaceholderResult::class.'::getActionCount')
            ->expect(function () {
                $action1 = ($this->createAction)('@A', '/prod1.php', '/test1.php');
                $action2 = ($this->createAction)('@B', '/prod2.php', '/test2.php');

                return (new PlaceholderResult(actions: [$action1, $action2]))->getActionCount();
            })
            ->toBe(2);
    });

    describe('getErrorCount', function (): void {
        it('returns count of errors')
            ->linksAndCovers(PlaceholderResult::class.'::getErrorCount')
            ->expect(fn () => (new PlaceholderResult(errors: ['Error 1', 'Error 2']))->getErrorCount())
            ->toBe(2);
    });

    describe('getWarningCount', function (): void {
        it('returns count of warnings')
            ->linksAndCovers(PlaceholderResult::class.'::getWarningCount')
            ->expect(fn () => (new PlaceholderResult(warnings: ['Warning 1']))->getWarningCount())
            ->toBe(1);
    });

    describe('getProductionFilesToModify', function (): void {
        it('returns unique production files')
            ->linksAndCovers(PlaceholderResult::class.'::getProductionFilesToModify')
            ->expect(function () {
                $action1 = ($this->createAction)('@A', '/prod1.php', '/test1.php');
                $action2 = ($this->createAction)('@B', '/prod1.php', '/test2.php');
                $action3 = ($this->createAction)('@C', '/prod2.php', '/test3.php');

                return (new PlaceholderResult(actions: [$action1, $action2, $action3]))->getProductionFilesToModify();
            })
            ->toBe(['/prod1.php', '/prod2.php']);
    });

    describe('getTestFilesToModify', function (): void {
        it('returns unique test files')
            ->linksAndCovers(PlaceholderResult::class.'::getTestFilesToModify')
            ->expect(function () {
                $action1 = ($this->createAction)('@A', '/prod1.php', '/test1.php');
                $action2 = ($this->createAction)('@B', '/prod2.php', '/test1.php');
                $action3 = ($this->createAction)('@C', '/prod3.php', '/test2.php');

                return (new PlaceholderResult(actions: [$action1, $action2, $action3]))->getTestFilesToModify();
            })
            ->toBe(['/test1.php', '/test2.php']);
    });

    describe('getActionsByPlaceholder', function (): void {
        it('groups actions by placeholder ID')
            ->linksAndCovers(PlaceholderResult::class.'::getActionsByPlaceholder')
            ->expect(function () {
                $action1 = ($this->createAction)('@A', '/prod1.php', '/test1.php');
                $action2 = ($this->createAction)('@A', '/prod2.php', '/test2.php');
                $action3 = ($this->createAction)('@B', '/prod3.php', '/test3.php');

                $result  = new PlaceholderResult(actions: [$action1, $action2, $action3]);
                $grouped = $result->getActionsByPlaceholder();

                return [
                    'keys'    => array_keys($grouped),
                    'a_count' => count($grouped['@A']),
                    'b_count' => count($grouped['@B']),
                ];
            })
            ->toMatchArray([
                'keys'    => ['@A', '@B'],
                'a_count' => 2,
                'b_count' => 1,
            ]);
    });

    describe('getActionsForProductionFile', function (): void {
        it('returns actions for specific production file')
            ->linksAndCovers(PlaceholderResult::class.'::getActionsForProductionFile')
            ->expect(function () {
                $action1 = ($this->createAction)('@A', '/prod1.php', '/test1.php');
                $action2 = ($this->createAction)('@B', '/prod1.php', '/test2.php');
                $action3 = ($this->createAction)('@C', '/prod2.php', '/test3.php');

                $result = new PlaceholderResult(actions: [$action1, $action2, $action3]);

                return count($result->getActionsForProductionFile('/prod1.php'));
            })
            ->toBe(2);
    });

    describe('getActionsForTestFile', function (): void {
        it('returns actions for specific test file')
            ->linksAndCovers(PlaceholderResult::class.'::getActionsForTestFile')
            ->expect(function () {
                $action1 = ($this->createAction)('@A', '/prod1.php', '/test1.php');
                $action2 = ($this->createAction)('@B', '/prod2.php', '/test1.php');
                $action3 = ($this->createAction)('@C', '/prod3.php', '/test2.php');

                $result = new PlaceholderResult(actions: [$action1, $action2, $action3]);

                return count($result->getActionsForTestFile('/test1.php'));
            })
            ->toBe(2);
    });

    describe('getSummary', function (): void {
        it('returns summary of result')
            ->linksAndCovers(PlaceholderResult::class.'::getSummary')
            ->expect(function () {
                $action1 = ($this->createAction)('@A', '/prod1.php', '/test1.php');
                $action2 = ($this->createAction)('@A', '/prod2.php', '/test2.php');
                $action3 = ($this->createAction)('@B', '/prod3.php', '/test3.php');

                $result = new PlaceholderResult(
                    actions: [$action1, $action2, $action3],
                    errors: ['Error 1'],
                    warnings: ['Warning 1', 'Warning 2'],
                );

                return $result->getSummary();
            })
            ->toMatchArray([
                'placeholders'     => 2,
                'actions'          => 3,
                'production_files' => 3,
                'test_files'       => 3,
                'errors'           => 1,
                'warnings'         => 2,
            ]);
    });
});
