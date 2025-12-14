<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Console\Output;

describe('Output', function (): void {
    beforeEach(function (): void {
        $this->output = new Output();
    });

    describe('summaryHeader', function (): void {
        it('returns self for method chaining')
            ->linksAndCovers(Output::class.'::summaryHeader')
            ->expect(fn () => $this->output->summaryHeader())
            ->toBeInstanceOf(Output::class);

        it('returns self with dry-run flag')
            ->linksAndCovers(Output::class.'::summaryHeader')
            ->expect(fn () => $this->output->summaryHeader(true))
            ->toBeInstanceOf(Output::class);
    });

    describe('summaryLine', function (): void {
        it('returns self for method chaining')
            ->linksAndCovers(Output::class.'::summaryLine')
            ->expect(fn () => $this->output->summaryLine('Label', 42))
            ->toBeInstanceOf(Output::class);

        it('accepts string values')
            ->linksAndCovers(Output::class.'::summaryLine')
            ->expect(fn () => $this->output->summaryLine('Status', 'OK'))
            ->toBeInstanceOf(Output::class);

        it('accepts custom label width')
            ->linksAndCovers(Output::class.'::summaryLine')
            ->expect(fn () => $this->output->summaryLine('Label', 10, 40))
            ->toBeInstanceOf(Output::class);
    });

    describe('summaryComplete', function (): void {
        it('returns self for method chaining')
            ->linksAndCovers(Output::class.'::summaryComplete')
            ->expect(fn () => $this->output->summaryComplete('Done!'))
            ->toBeInstanceOf(Output::class);
    });

    describe('color methods', function (): void {
        it('applies red color')
            ->linksAndCovers(Output::class.'::red')
            ->expect(fn () => $this->output->red('test'))
            ->toBeString();

        it('applies green color')
            ->linksAndCovers(Output::class.'::green')
            ->expect(fn () => $this->output->green('test'))
            ->toBeString();

        it('applies yellow color')
            ->linksAndCovers(Output::class.'::yellow')
            ->expect(fn () => $this->output->yellow('test'))
            ->toBeString();

        it('applies cyan color')
            ->linksAndCovers(Output::class.'::cyan')
            ->expect(fn () => $this->output->cyan('test'))
            ->toBeString();

        it('applies gray color')
            ->linksAndCovers(Output::class.'::gray')
            ->expect(fn () => $this->output->gray('test'))
            ->toBeString();

        it('applies bold style')
            ->linksAndCovers(Output::class.'::bold')
            ->expect(fn () => $this->output->bold('test'))
            ->toBeString();

        it('applies dim style')
            ->linksAndCovers(Output::class.'::dim')
            ->expect(fn () => $this->output->dim('test'))
            ->toBeString();
    });

    describe('basic output methods', function (): void {
        it('writeln returns self')
            ->linksAndCovers(Output::class.'::writeln')
            ->expect(fn () => $this->output->writeln('test'))
            ->toBeInstanceOf(Output::class);

        it('write returns self')
            ->linksAndCovers(Output::class.'::write')
            ->expect(fn () => $this->output->write('test'))
            ->toBeInstanceOf(Output::class);

        it('newLine returns self')
            ->linksAndCovers(Output::class.'::newLine')
            ->expect(fn () => $this->output->newLine())
            ->toBeInstanceOf(Output::class);

        it('newLine accepts count parameter')
            ->linksAndCovers(Output::class.'::newLine')
            ->expect(fn () => $this->output->newLine(3))
            ->toBeInstanceOf(Output::class);
    });

    describe('section methods', function (): void {
        it('title returns self')
            ->linksAndCovers(Output::class.'::title')
            ->expect(fn () => $this->output->title('Test Title'))
            ->toBeInstanceOf(Output::class);

        it('section returns self')
            ->linksAndCovers(Output::class.'::section')
            ->expect(fn () => $this->output->section('Section'))
            ->toBeInstanceOf(Output::class);

        it('listItem returns self')
            ->linksAndCovers(Output::class.'::listItem')
            ->expect(fn () => $this->output->listItem('Item'))
            ->toBeInstanceOf(Output::class);

        it('listItem accepts custom prefix')
            ->linksAndCovers(Output::class.'::listItem')
            ->expect(fn () => $this->output->listItem('Item', '→'))
            ->toBeInstanceOf(Output::class);
    });

    describe('message methods', function (): void {
        it('success returns self')
            ->linksAndCovers(Output::class.'::success')
            ->expect(fn () => $this->output->success('Success!'))
            ->toBeInstanceOf(Output::class);

        it('warning returns self')
            ->linksAndCovers(Output::class.'::warning')
            ->expect(fn () => $this->output->warning('Warning!'))
            ->toBeInstanceOf(Output::class);

        it('info returns self')
            ->linksAndCovers(Output::class.'::info')
            ->expect(fn () => $this->output->info('Info'))
            ->toBeInstanceOf(Output::class);

        it('comment returns self')
            ->linksAndCovers(Output::class.'::comment')
            ->expect(fn () => $this->output->comment('Comment'))
            ->toBeInstanceOf(Output::class);

        it('error returns self')
            ->linksAndCovers(Output::class.'::error')
            ->expect(fn () => $this->output->error('Error'))
            ->toBeInstanceOf(Output::class);
    });

    describe('table method', function (): void {
        it('returns self for empty rows')
            ->linksAndCovers(Output::class.'::table')
            ->expect(fn () => $this->output->table(['Header'], []))
            ->toBeInstanceOf(Output::class);

        it('returns self for table with data')
            ->linksAndCovers(Output::class.'::table')
            ->expect(fn () => $this->output->table(['Col1', 'Col2'], [['A', 'B'], ['C', 'D']]))
            ->toBeInstanceOf(Output::class);
    });

    describe('color support detection', function (): void {
        it('returns boolean for supportsColor')
            ->linksAndCovers(Output::class.'::supportsColor')
            ->expect(fn () => $this->output->supportsColor())
            ->toBeBool();
    });
});
