<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use SeviaFlow\DevTools\Console\Commands\CacheClearCommand;
use SeviaFlow\DevTools\Console\Commands\CacheCommand;
use SeviaFlow\DevTools\Console\Commands\DiscoverCommand;
use SeviaFlow\DevTools\Console\Commands\DoctorCommand;
use SeviaFlow\DevTools\Console\Commands\FoundationCommand;
use SeviaFlow\DevTools\Console\Commands\InstallCommand;
use SeviaFlow\DevTools\Console\Commands\ListCommand;
use SeviaFlow\DevTools\Console\Commands\MakeActionCommand;
use SeviaFlow\DevTools\Console\Commands\MakeBulkActionCommand;
use SeviaFlow\DevTools\Console\Commands\MakeFormCommand;
use SeviaFlow\DevTools\Console\Commands\MakeResourceCommand;
use SeviaFlow\DevTools\Console\Commands\MakeSectionCommand;
use SeviaFlow\DevTools\Console\Commands\MakeTableCommand;
use SeviaFlow\DevTools\Console\Commands\MakeWorkflowHandlerCommand;
use SeviaFlow\DevTools\Console\Commands\StatusCommand;

it('registers eloquent admin commands in artisan', function (): void {
    $commands = array_keys(app(ConsoleKernel::class)->all());

    expect($commands)->toContain('seviaflow:doctor');
    expect($commands)->toContain('seviaflow:cache');
    expect($commands)->toContain('seviaflow:cache-clear');
    expect($commands)->toContain('seviaflow:discover');
    expect($commands)->toContain('seviaflow:foundation');
    expect($commands)->toContain('seviaflow:install');
    expect($commands)->toContain('seviaflow:list');
    expect($commands)->toContain('seviaflow:make-action');
    expect($commands)->toContain('seviaflow:make-bulk-action');
    expect($commands)->toContain('seviaflow:make-form');
    expect($commands)->toContain('seviaflow:make-resource');
    expect($commands)->toContain('seviaflow:make-section');
    expect($commands)->toContain('seviaflow:make-table');
    expect($commands)->toContain('seviaflow:make-workflow-handler');
    expect($commands)->toContain('seviaflow:status');
    $artisan = app(ConsoleKernel::class)->all();

    expect($artisan['seviaflow:doctor'])->toBeInstanceOf(DoctorCommand::class);
    expect($artisan['seviaflow:cache'])->toBeInstanceOf(CacheCommand::class);
    expect($artisan['seviaflow:cache-clear'])->toBeInstanceOf(CacheClearCommand::class);
    expect($artisan['seviaflow:discover'])->toBeInstanceOf(DiscoverCommand::class);
    expect($artisan['seviaflow:foundation'])->toBeInstanceOf(FoundationCommand::class);
    expect($artisan['seviaflow:install'])->toBeInstanceOf(InstallCommand::class);
    expect($artisan['seviaflow:list'])->toBeInstanceOf(ListCommand::class);
    expect($artisan['seviaflow:make-action'])->toBeInstanceOf(MakeActionCommand::class);
    expect($artisan['seviaflow:make-bulk-action'])->toBeInstanceOf(MakeBulkActionCommand::class);
    expect($artisan['seviaflow:make-form'])->toBeInstanceOf(MakeFormCommand::class);
    expect($artisan['seviaflow:make-resource'])->toBeInstanceOf(MakeResourceCommand::class);
    expect($artisan['seviaflow:make-section'])->toBeInstanceOf(MakeSectionCommand::class);
    expect($artisan['seviaflow:make-table'])->toBeInstanceOf(MakeTableCommand::class);
    expect($artisan['seviaflow:make-workflow-handler'])->toBeInstanceOf(MakeWorkflowHandlerCommand::class);
    expect($artisan['seviaflow:status'])->toBeInstanceOf(StatusCommand::class);
});

it('uses seviaflow namespace for package command signatures', function (): void {
    $doctorSignature = app(DoctorCommand::class)->getName();
    $cacheSignature = app(CacheCommand::class)->getName();
    $cacheClearSignature = app(CacheClearCommand::class)->getName();
    $discoverSignature = app(DiscoverCommand::class)->getName();
    $foundationSignature = app(FoundationCommand::class)->getName();
    $installSignature = app(InstallCommand::class)->getName();
    $listSignature = app(ListCommand::class)->getName();
    $makeActionSignature = app(MakeActionCommand::class)->getName();
    $makeBulkActionSignature = app(MakeBulkActionCommand::class)->getName();
    $makeFormSignature = app(MakeFormCommand::class)->getName();
    $makeResourceSignature = app(MakeResourceCommand::class)->getName();
    $makeSectionSignature = app(MakeSectionCommand::class)->getName();
    $makeTableSignature = app(MakeTableCommand::class)->getName();
    $makeWorkflowHandlerSignature = app(MakeWorkflowHandlerCommand::class)->getName();
    $statusSignature = app(StatusCommand::class)->getName();

    expect($doctorSignature)->toStartWith('seviaflow:');
    expect($cacheSignature)->toStartWith('seviaflow:');
    expect($cacheClearSignature)->toStartWith('seviaflow:');
    expect($discoverSignature)->toStartWith('seviaflow:');
    expect($foundationSignature)->toStartWith('seviaflow:');
    expect($installSignature)->toStartWith('seviaflow:');
    expect($listSignature)->toStartWith('seviaflow:');
    expect($makeActionSignature)->toStartWith('seviaflow:');
    expect($makeBulkActionSignature)->toStartWith('seviaflow:');
    expect($makeFormSignature)->toStartWith('seviaflow:');
    expect($makeResourceSignature)->toStartWith('seviaflow:');
    expect($makeSectionSignature)->toStartWith('seviaflow:');
    expect($makeTableSignature)->toStartWith('seviaflow:');
    expect($makeWorkflowHandlerSignature)->toStartWith('seviaflow:');
    expect($statusSignature)->toStartWith('seviaflow:');
});
