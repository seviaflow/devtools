<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-make-workflow-handler-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }

    mkdir($tmp, 0755, true);

    config()->set('seviaflow.paths.resources', $tmp.'/app/SeviaFlow/Resources');
    config()->set('seviaflow.namespaces.resources', 'App\\SeviaFlow\\Resources');

    pest_test_case()->artisan('seviaflow:make-resource InvoiceResource --model=User')->assertSuccessful();
});

afterEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-make-workflow-handler-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }
});

it('command exists', function (): void {
    $commands = array_keys(app(ConsoleKernel::class)->all());

    expect($commands)->toContain('seviaflow:make-workflow-handler');
});

it('generates handler class', function (): void {
    pest_test_case()->artisan('seviaflow:make-workflow-handler SendInvoiceTransition --resource=InvoiceResource')
        ->assertSuccessful();

    $path = sys_get_temp_dir().'/seviaflow-make-workflow-handler-tests/app/SeviaFlow/Resources/InvoiceResource/Workflows/SendInvoiceTransition.php';

    expect(is_file($path))->toBeTrue();
});

it('generated namespace is correct', function (): void {
    pest_test_case()->artisan('seviaflow:make-workflow-handler SendInvoiceTransition --resource=InvoiceResource')
        ->assertSuccessful();

    $path = sys_get_temp_dir().'/seviaflow-make-workflow-handler-tests/app/SeviaFlow/Resources/InvoiceResource/Workflows/SendInvoiceTransition.php';

    expect(file_get_contents($path))->toContain('namespace App\\SeviaFlow\\Resources\\InvoiceResource\\Workflows;');
});

it('generated class is invokable and imports transition context', function (): void {
    pest_test_case()->artisan('seviaflow:make-workflow-handler SendInvoiceTransition --resource=InvoiceResource')
        ->assertSuccessful();

    $path = sys_get_temp_dir().'/seviaflow-make-workflow-handler-tests/app/SeviaFlow/Resources/InvoiceResource/Workflows/SendInvoiceTransition.php';
    $content = file_get_contents($path);

    expect($content)->toContain('use SeviaFlow\\Core\\Resources\\Workflows\\TransitionContext;')
        ->and($content)->toContain('public function __invoke(mixed $record, TransitionContext $context): void');
});

it('fails clearly when resource is missing', function (): void {
    pest_test_case()->artisan('seviaflow:make-workflow-handler SendInvoiceTransition --resource=MissingResource')
        ->assertFailed();
});

it('does not overwrite without force', function (): void {
    $path = sys_get_temp_dir().'/seviaflow-make-workflow-handler-tests/app/SeviaFlow/Resources/InvoiceResource/Workflows/SendInvoiceTransition.php';

    pest_test_case()->artisan('seviaflow:make-workflow-handler SendInvoiceTransition --resource=InvoiceResource')
        ->assertSuccessful();

    file_put_contents($path, 'old-handler-content');

    pest_test_case()->artisan('seviaflow:make-workflow-handler SendInvoiceTransition --resource=InvoiceResource')
        ->assertFailed();

    expect(file_get_contents($path))->toBe('old-handler-content');
});

it('force overwrites', function (): void {
    $path = sys_get_temp_dir().'/seviaflow-make-workflow-handler-tests/app/SeviaFlow/Resources/InvoiceResource/Workflows/SendInvoiceTransition.php';

    pest_test_case()->artisan('seviaflow:make-workflow-handler SendInvoiceTransition --resource=InvoiceResource')
        ->assertSuccessful();

    file_put_contents($path, 'old-handler-content');

    pest_test_case()->artisan('seviaflow:make-workflow-handler SendInvoiceTransition --resource=InvoiceResource --force')
        ->assertSuccessful();

    expect(file_get_contents($path))->toContain('final class SendInvoiceTransition');
    expect(file_get_contents($path) === 'old-handler-content')->toBeFalse();
});
