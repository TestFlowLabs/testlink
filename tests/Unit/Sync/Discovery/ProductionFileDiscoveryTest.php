<?php

declare(strict_types=1);

use TestFlowLabs\TestLink\Sync\Discovery\ProductionFileDiscovery;
use TestFlowLabs\TestLink\Sync\Exception\ProductionFileNotFoundException;

describe('ProductionFileDiscovery', function (): void {
    beforeEach(function (): void {
        $this->discovery = new ProductionFileDiscovery();
        $this->testDir   = sys_get_temp_dir().'/testlink-prod-discovery';
        @mkdir($this->testDir.'/src/Services', 0777, true);
        @mkdir($this->testDir.'/app/Models', 0777, true);
    });

    afterEach(function (): void {
        // Clean up temp files recursively
        $srcFiles = glob($this->testDir.'/src/Services/*.php') ?: [];
        array_map('unlink', $srcFiles);
        $appFiles = glob($this->testDir.'/app/Models/*.php') ?: [];
        array_map('unlink', $appFiles);
        @rmdir($this->testDir.'/src/Services');
        @rmdir($this->testDir.'/app/Models');
        @rmdir($this->testDir.'/src');
        @rmdir($this->testDir.'/app');
        @rmdir($this->testDir);
    });

    describe('extractClassName', function (): void {
        it('extracts class name from method identifier', function (): void {
            $result = $this->discovery->extractClassName('App\\Services\\UserService::create');

            expect($result)->toBe('App\\Services\\UserService');
        });

        it('handles identifier without method', function (): void {
            $result = $this->discovery->extractClassName('App\\Services\\UserService');

            expect($result)->toBe('App\\Services\\UserService');
        });
    });

    describe('extractMethodName', function (): void {
        it('extracts method name from identifier', function (): void {
            $result = $this->discovery->extractMethodName('App\\Services\\UserService::create');

            expect($result)->toBe('create');
        });

        it('returns null when no method', function (): void {
            $result = $this->discovery->extractMethodName('App\\Services\\UserService');

            expect($result)->toBeNull();
        });
    });

    describe('productionFileExists', function (): void {
        it('returns true when file can be found', function (): void {
            $this->discovery->setProjectRoot($this->testDir);

            // Create a production file
            file_put_contents(
                $this->testDir.'/src/Services/UserService.php',
                '<?php class UserService {}'
            );

            $result = $this->discovery->productionFileExists('Src\\Services\\UserService::create');

            expect($result)->toBeTrue();
        });

        it('returns false when file cannot be found', function (): void {
            $this->discovery->setProjectRoot($this->testDir);

            $result = $this->discovery->productionFileExists('App\\NonExistent\\SomeClass::method');

            expect($result)->toBeFalse();
        });
    });

    describe('findProductionFile', function (): void {
        it('finds production file via PSR-4 path in src/', function (): void {
            $this->discovery->setProjectRoot($this->testDir);

            // Create a production file
            file_put_contents(
                $this->testDir.'/src/Services/UserService.php',
                '<?php class UserService {}'
            );

            $result = $this->discovery->findProductionFile('Src\\Services\\UserService::create');

            expect($result)->toContain('UserService.php');
        });

        it('finds production file via PSR-4 path in app/', function (): void {
            $this->discovery->setProjectRoot($this->testDir);

            // Create a production file in app directory (Laravel style)
            file_put_contents(
                $this->testDir.'/app/Models/User.php',
                '<?php class User {}'
            );

            $result = $this->discovery->findProductionFile('App\\Models\\User::create');

            expect($result)->toContain('User.php');
        });

        it('throws when file not found', function (): void {
            $this->discovery->setProjectRoot($this->testDir);

            $this->discovery->findProductionFile('App\\NonExistent\\SomeClass::method');
        })->throws(ProductionFileNotFoundException::class);
    });

    describe('setProjectRoot', function (): void {
        it('returns self for fluent interface', function (): void {
            $result = $this->discovery->setProjectRoot('/tmp');

            expect($result)->toBe($this->discovery);
        });
    });

    describe('findViaReflection', function (): void {
        it('finds existing class via reflection', function (): void {
            // Test with an existing class
            $result = $this->discovery->findProductionFile('TestFlowLabs\\TestLink\\Sync\\SyncResult::merge');

            expect($result)->toContain('SyncResult.php');
        });
    });
});
