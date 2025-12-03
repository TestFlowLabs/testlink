<?php

declare(strict_types=1);

namespace TestFlowLabs\TestLink\Console;

use Composer\InstalledVersions;
use TestFlowLabs\TestLink\Adapter\CompositeAdapter;
use TestFlowLabs\TestLink\Console\Command\PairCommand;
use TestFlowLabs\TestLink\Console\Command\SyncCommand;
use TestFlowLabs\TestLink\Console\Command\ReportCommand;
use TestFlowLabs\TestLink\Console\Command\ValidateCommand;

/**
 * Main CLI application for TestLink.
 *
 * Native PHP implementation without external dependencies.
 */
final class Application
{
    private const NAME = 'TestLink';

    private readonly Output $output;
    private readonly ArgumentParser $parser;
    private readonly CompositeAdapter $adapter;

    /** @var array<string, callable(ArgumentParser, Output): int> */
    private array $commands = [];

    public function __construct()
    {
        $this->output  = new Output();
        $this->parser  = new ArgumentParser();
        $this->adapter = new CompositeAdapter();

        $this->registerCommands();
    }

    /**
     * Run the CLI application.
     *
     * @param  list<string>  $argv
     */
    public function run(array $argv): int
    {
        $this->parser->parse($argv);

        $command = $this->parser->getCommand();

        // Handle help flag
        if ($this->parser->hasOption('help') || $this->parser->hasOption('h')) {
            if ($command !== null && isset($this->commands[$command])) {
                return $this->showCommandHelp($command);
            }

            return $this->showHelp();
        }

        // Handle version flag
        if ($this->parser->hasOption('version') || $this->parser->hasOption('v')) {
            return $this->showVersion();
        }

        // No command provided
        if ($command === null) {
            return $this->showHelp();
        }

        // Unknown command
        if (!isset($this->commands[$command])) {
            $this->output->error("Unknown command: {$command}");
            $this->output->writeln();
            $this->showAvailableCommands();

            return 1;
        }

        // Execute command
        try {
            return $this->commands[$command]($this->parser, $this->output);
        } catch (\Throwable $e) {
            $this->output->error("Error: {$e->getMessage()}");

            if ($this->parser->hasOption('verbose')) {
                $this->output->writeln();
                $this->output->writeln($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Register available commands.
     */
    private function registerCommands(): void
    {
        $reportCommand   = new ReportCommand();
        $validateCommand = new ValidateCommand();
        $syncCommand     = new SyncCommand();
        $pairCommand     = new PairCommand();

        $this->commands['report'] = fn (ArgumentParser $parser, Output $output): int => $reportCommand->execute($parser, $output);

        $this->commands['validate'] = fn (ArgumentParser $parser, Output $output): int => $validateCommand->execute($parser, $output);

        $this->commands['sync'] = fn (ArgumentParser $parser, Output $output): int => $syncCommand->execute($parser, $output);

        $this->commands['pair'] = fn (ArgumentParser $parser, Output $output): int => $pairCommand->execute($parser, $output);
    }

    /**
     * Show help information.
     */
    private function showHelp(): int
    {
        $this->showVersion();
        $this->output->newLine();

        $frameworks    = $this->adapter->getAvailableFrameworks();
        $frameworkList = $frameworks !== [] ? implode(', ', $frameworks) : 'none detected';

        $this->output->writeln($this->output->gray("  Detected frameworks: {$frameworkList}"));
        $this->output->newLine();

        $this->output->section('USAGE');
        $this->output->writeln('    testlink <command> [options]');

        $this->showAvailableCommands();

        $this->output->section('GLOBAL OPTIONS');
        $this->output->listItem('--help, -h        Show help information');
        $this->output->listItem('--version, -v     Show version');
        $this->output->listItem('--verbose         Show detailed output');
        $this->output->listItem('--no-color        Disable colored output');

        $this->output->newLine();
        $this->output->writeln($this->output->gray('  Run "testlink <command> --help" for command-specific help.'));
        $this->output->newLine();

        return 0;
    }

    /**
     * Show available commands.
     */
    private function showAvailableCommands(): void
    {
        $this->output->section('COMMANDS');
        $this->output->listItem($this->output->cyan('report').'      Show coverage links report');
        $this->output->listItem($this->output->cyan('validate').'    Validate coverage link synchronization');
        $this->output->listItem($this->output->cyan('sync').'        Sync coverage links across test files');
        $this->output->listItem($this->output->cyan('pair').'        Resolve placeholder markers into real links');
    }

    /**
     * Show command-specific help.
     */
    private function showCommandHelp(string $command): int
    {
        $this->showVersion();
        $this->output->newLine();

        match ($command) {
            'report'   => $this->showReportHelp(),
            'validate' => $this->showValidateHelp(),
            'sync'     => $this->showSyncHelp(),
            'pair'     => $this->showPairHelp(),
            default    => $this->showHelp(),
        };

        return 0;
    }

    /**
     * Show report command help.
     */
    private function showReportHelp(): void
    {
        $this->output->section('REPORT COMMAND');
        $this->output->writeln('    testlink report [options]');
        $this->output->newLine();
        $this->output->writeln('  Show coverage links from test files.');
        $this->output->newLine();

        $this->output->section('OPTIONS');
        $this->output->listItem('--json            Output as JSON');
        $this->output->listItem('--path=<dir>      Limit scan to directory');
        $this->output->listItem('--framework=<fw>  Filter by framework (pest, phpunit)');
        $this->output->newLine();

        $this->output->section('EXAMPLES');
        $this->output->writeln('    testlink report');
        $this->output->writeln('    testlink report --json');
        $this->output->writeln('    testlink report --path=src/Services');
        $this->output->newLine();
    }

    /**
     * Show validate command help.
     */
    private function showValidateHelp(): void
    {
        $this->output->section('VALIDATE COMMAND');
        $this->output->writeln('    testlink validate [options]');
        $this->output->newLine();
        $this->output->writeln('  Validate coverage links in test files.');
        $this->output->newLine();

        $this->output->section('OPTIONS');
        $this->output->listItem('--strict          Fail on warnings');
        $this->output->listItem('--json            Output as JSON');
        $this->output->listItem('--path=<dir>      Limit scan to directory');
        $this->output->newLine();

        $this->output->section('EXAMPLES');
        $this->output->writeln('    testlink validate');
        $this->output->writeln('    testlink validate --strict');
        $this->output->newLine();
    }

    /**
     * Show sync command help.
     */
    private function showSyncHelp(): void
    {
        $this->output->section('SYNC COMMAND');
        $this->output->writeln('    testlink sync [options]');
        $this->output->newLine();
        $this->output->writeln('  Sync coverage links across test files (PHPUnit and Pest).');
        $this->output->newLine();

        $this->output->section('OPTIONS');
        $this->output->listItem('--dry-run         Preview changes without modifying files');
        $this->output->listItem('--link-only       Use links() instead of linksAndCovers()');
        $this->output->listItem('--prune           Remove orphaned link calls');
        $this->output->listItem('--force           Required with --prune');
        $this->output->listItem('--path=<dir>      Limit scan to directory');
        $this->output->listItem('--framework=<fw>  Target framework (pest, phpunit, auto)');
        $this->output->newLine();

        $this->output->section('EXAMPLES');
        $this->output->writeln('    testlink sync --dry-run');
        $this->output->writeln('    testlink sync');
        $this->output->writeln('    testlink sync --link-only');
        $this->output->writeln('    testlink sync --prune --force');
        $this->output->newLine();
    }

    /**
     * Show pair command help.
     */
    private function showPairHelp(): void
    {
        $this->output->section('PAIR COMMAND');
        $this->output->writeln('    testlink pair [options]');
        $this->output->newLine();
        $this->output->writeln('  Resolve placeholder markers (@A, @user-create) into real test-production links.');
        $this->output->newLine();

        $this->output->section('PLACEHOLDER SYNTAX');
        $this->output->writeln('    Production code:');
        $this->output->writeln("      #[TestedBy('@A')]");
        $this->output->writeln("      #[TestedBy('@user-create')]");
        $this->output->newLine();
        $this->output->writeln('    Test code (Pest):');
        $this->output->writeln("      ->linksAndCovers('@A')");
        $this->output->writeln("      ->links('@user-create')");
        $this->output->newLine();
        $this->output->writeln('    Test code (PHPUnit):');
        $this->output->writeln("      #[LinksAndCovers('@A')]");
        $this->output->writeln("      #[Links('@user-create')]");
        $this->output->newLine();

        $this->output->section('OPTIONS');
        $this->output->listItem('--dry-run               Preview changes without modifying files');
        $this->output->listItem('--placeholder=<id>      Resolve only a specific placeholder (e.g., @A)');
        $this->output->newLine();

        $this->output->section('EXAMPLES');
        $this->output->writeln('    testlink pair --dry-run');
        $this->output->writeln('    testlink pair');
        $this->output->writeln('    testlink pair --placeholder=@A');
        $this->output->newLine();
    }

    /**
     * Show version information.
     */
    private function showVersion(): int
    {
        $version = 'dev';

        try {
            $version = InstalledVersions::getPrettyVersion('testflowlabs/testlink') ?? 'dev';
        } catch (\Throwable) {
            // Package not installed via Composer
        }

        $this->output->writeln();
        $this->output->writeln($this->output->bold($this->output->cyan('  '.self::NAME))." {$version}");

        return 0;
    }
}
