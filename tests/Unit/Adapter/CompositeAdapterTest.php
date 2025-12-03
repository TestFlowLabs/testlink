<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Adapter\PestAdapter;
use TestFlowLabs\TestLink\Adapter\PhpUnitAdapter;
use TestFlowLabs\TestLink\Adapter\CompositeAdapter;
use TestFlowLabs\TestLink\Contract\FrameworkAdapterInterface;

beforeEach(function (): void {
    $this->adapter      = new CompositeAdapter();
    $this->fixturesPath = __DIR__.'/../../Fixtures';
});

test('it returns available adapters')
    ->linksAndCovers(CompositeAdapter::class.'::getAdapters')
    ->expect(fn () => $this->adapter->getAdapters())
    ->toBeArray()
    ->each->toBeInstanceOf(FrameworkAdapterInterface::class);

test('it returns available framework names')
    ->linksAndCovers(CompositeAdapter::class.'::getAvailableFrameworks')
    ->expect(fn () => $this->adapter->getAvailableFrameworks())
    ->toBeArray();

test('it gets adapter by name')
    ->linksAndCovers(CompositeAdapter::class.'::getAdapterByName')
    ->expect(fn () => $this->adapter->getAdapterByName('pest'))
    ->toBeInstanceOf(PestAdapter::class);

test('it checks framework availability')
    ->linksAndCovers(CompositeAdapter::class.'::hasFramework')
    ->expect(fn () => $this->adapter->hasFramework('pest'))
    ->toBeTrue();

test('it returns null for unknown framework')
    ->linksAndCovers(CompositeAdapter::class.'::getAdapterByName')
    ->expect(fn () => $this->adapter->getAdapterByName('unknown'))
    ->toBeNull();

test('it gets adapter for pest file')
    ->linksAndCovers(CompositeAdapter::class.'::getAdapterForFile')
    ->expect(fn () => $this->adapter->getAdapterForFile($this->fixturesPath.'/Pest/SimpleTest.php'))
    ->toBeInstanceOf(PestAdapter::class);

test('it gets adapter for phpunit file')
    ->linksAndCovers(CompositeAdapter::class.'::getAdapterForFile')
    ->expect(fn () => $this->adapter->getAdapterForFile($this->fixturesPath.'/PhpUnit/SimpleTestCase.php'))
    ->toBeInstanceOf(PhpUnitAdapter::class);

test('it returns primary adapter')
    ->linksAndCovers(CompositeAdapter::class.'::getPrimaryAdapter')
    ->expect(fn () => $this->adapter->getPrimaryAdapter())
    ->toBeInstanceOf(FrameworkAdapterInterface::class);

test('it collects all test file patterns')
    ->linksAndCovers(CompositeAdapter::class.'::getAllTestFilePatterns')
    ->expect(fn () => $this->adapter->getAllTestFilePatterns())
    ->toBeArray()
    ->not->toBeEmpty();
