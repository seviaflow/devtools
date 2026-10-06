<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use SeviaFlow\DevTools\Console\Commands\DoctorCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Fixtures\Resources\InlineExampleUserResource;

beforeEach(function (): void {
    $storageLink = public_path('storage');

    if (! is_dir($storageLink)) {
        mkdir($storageLink, 0755, true);
    }

    if (! Schema::hasTable('seviaflow_workflow_activities')) {
        Schema::create('seviaflow_workflow_activities', function (Blueprint $table): void {
            $table->id();
            $table->string('resource');
            $table->string('record_type');
            $table->string('record_id');
            $table->string('transition');
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->string('user_type')->nullable();
            $table->string('user_id')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('seviaflow_roles')) {
        Schema::create('seviaflow_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('seviaflow_permissions')) {
        Schema::create('seviaflow_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id');
            $table->string('resource');
            $table->string('ability');
            $table->boolean('allowed')->default(true);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('seviaflow_roleables')) {
        Schema::create('seviaflow_roleables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->timestamps();
        });
    }
});

afterEach(function (): void {
    $storageLink = public_path('storage');

    if (is_link($storageLink) || is_file($storageLink)) {
        unlink($storageLink);

        return;
    }

    if (is_dir($storageLink)) {
        File::deleteDirectory($storageLink);
    }
});

it('doctor command exists', function (): void {
    $commands = array_keys(app(ConsoleKernel::class)->all());

    expect($commands)->toContain('seviaflow:doctor');
});

it('successful checks return success', function (): void {
    config()->set('seviaflow.resources', [
        'users' => InlineExampleUserResource::class,
    ]);
    config()->set('seviaflow.permissions.enabled', false);

    $command = new DoctorCommand;

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $exitCode = $tester->execute([], ['interactive' => false]);
    $output = $tester->getDisplay();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('SeviaFlow Doctor');
    expect($output)->toContain('Core config loaded.');

    if (config()->has('seviaflow-ui')) {
        expect($output)->toContain('Routes registered under /admin.');
    } else {
        expect($output)->toContain('UI routes skipped because SeviaFlow UI is not installed or disabled.');
    }

    expect($output)->toContain('1 resources registered.');
    expect($output)->toContain('Workflow activity table exists.');
});

it('invalid resource class produces failure', function (): void {
    config()->set('seviaflow.resources', [
        'orders' => 'App\\SeviaFlow\\Resources\\MissingOrderResource',
    ]);

    $command = new DoctorCommand;

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $exitCode = $tester->execute([], ['interactive' => false]);
    $output = $tester->getDisplay();

    expect($exitCode)->toBe(1);
    expect($output)->toContain('Resource "orders" points to missing class App\\SeviaFlow\\Resources\\MissingOrderResource.');
});

it('missing storage link produces warning', function (): void {
    config()->set('seviaflow.resources', [
        'users' => InlineExampleUserResource::class,
    ]);

    $storageLink = public_path('storage');

    if (is_link($storageLink) || is_file($storageLink)) {
        unlink($storageLink);
    } elseif (is_dir($storageLink)) {
        File::deleteDirectory($storageLink);
    }

    $command = new DoctorCommand;

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $exitCode = $tester->execute([], ['interactive' => false]);
    $output = $tester->getDisplay();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('FileField/ImageField use public disk but storage link was not detected.');
});

it('permissions enabled with missing tables produces failure', function (): void {
    config()->set('seviaflow.permissions.enabled', true);

    if (Schema::hasTable('seviaflow_permissions')) {
        Schema::drop('seviaflow_permissions');
    }

    $command = new DoctorCommand;

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $exitCode = $tester->execute([], ['interactive' => false]);
    $output = $tester->getDisplay();

    expect($exitCode)->toBe(1);
    expect($output)->toContain('Permissions enabled but missing tables: seviaflow_permissions.');

    if (! Schema::hasTable('seviaflow_permissions')) {
        Schema::create('seviaflow_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('resource');
            $table->string('ability');
            $table->boolean('allowed')->default(true);
            $table->timestamps();
        });
    }
});

it('doctor warns for length_aware pagination mode', function (): void {
    config()->set('seviaflow.performance.pagination.mode', 'length_aware');

    $command = new DoctorCommand;

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $exitCode = $tester->execute([], ['interactive' => false]);
    $output = $tester->getDisplay();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Pagination mode is length_aware. Consider simple pagination for large datasets to avoid expensive COUNT queries.');
});

it('doctor warns for contains search mode', function (): void {
    config()->set('seviaflow.performance.search.default_mode', 'contains');
    config()->set('seviaflow.performance.search.mode', 'contains');

    $command = new DoctorCommand;

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $exitCode = $tester->execute([], ['interactive' => false]);
    $output = $tester->getDisplay();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Default search mode is contains. This can be expensive on large datasets; consider starts_with or exact.');
});

it('doctor warns for high max_per_page', function (): void {
    config()->set('seviaflow.performance.pagination.max_per_page', 500);

    $command = new DoctorCommand;

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $exitCode = $tester->execute([], ['interactive' => false]);
    $output = $tester->getDisplay();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Pagination max_per_page is high (500). Consider a lower cap to protect large datasets.');
});

it('doctor warns when openapi cache is disabled in production-like environment', function (): void {
    config()->set('app.env', 'production');
    config()->set('seviaflow.performance.cache.enabled', true);
    config()->set('seviaflow-api.openapi.cache.enabled', false);

    $command = new DoctorCommand;

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $exitCode = $tester->execute([], ['interactive' => false]);
    $output = $tester->getDisplay();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('OpenAPI cache is disabled in production. Consider enabling performance.cache.openapi for docs/API schema requests.');
});

it('doctor warns for high export and import limits', function (): void {
    config()->set('seviaflow.export.max_rows', 15000);
    config()->set('seviaflow.import.max_rows', 7000);

    $command = new DoctorCommand;

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $exitCode = $tester->execute([], ['interactive' => false]);
    $output = $tester->getDisplay();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Export max_rows is high (15000). Consider a lower limit for safer memory/runtime usage.')
        ->and($output)->toContain('Import max_rows is high (7000). Consider a lower limit to reduce failure blast radius.');
});
