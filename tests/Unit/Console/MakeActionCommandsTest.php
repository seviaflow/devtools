<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-make-actions-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }

    mkdir($tmp, 0755, true);

    config()->set('seviaflow.paths.resources', $tmp.'/app/SeviaFlow/Resources');
    config()->set('seviaflow.namespaces.resources', 'App\\SeviaFlow\\Resources');

    pest_test_case()->artisan('seviaflow:make-resource UserResource --model=User')->assertSuccessful();
});

afterEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-make-actions-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }
});

it('make-action generates class', function (): void {
    pest_test_case()->artisan('seviaflow:make-action DeactivateUserAction --resource=UserResource')
        ->assertSuccessful();

    $path = sys_get_temp_dir().'/seviaflow-make-actions-tests/app/SeviaFlow/Resources/UserResource/Actions/DeactivateUserAction.php';

    expect(is_file($path))->toBeTrue();
});

it('make-bulk-action generates class', function (): void {
    pest_test_case()->artisan('seviaflow:make-bulk-action DeactivateUsersBulkAction --resource=UserResource')
        ->assertSuccessful();

    $path = sys_get_temp_dir().'/seviaflow-make-actions-tests/app/SeviaFlow/Resources/UserResource/BulkActions/DeactivateUsersBulkAction.php';

    expect(is_file($path))->toBeTrue();
});

it('generated namespaces are correct', function (): void {
    pest_test_case()->artisan('seviaflow:make-action DeactivateUserAction --resource=UserResource')
        ->assertSuccessful();
    pest_test_case()->artisan('seviaflow:make-bulk-action DeactivateUsersBulkAction --resource=UserResource')
        ->assertSuccessful();

    $actionPath = sys_get_temp_dir().'/seviaflow-make-actions-tests/app/SeviaFlow/Resources/UserResource/Actions/DeactivateUserAction.php';
    $bulkPath = sys_get_temp_dir().'/seviaflow-make-actions-tests/app/SeviaFlow/Resources/UserResource/BulkActions/DeactivateUsersBulkAction.php';

    expect(file_get_contents($actionPath))->toContain('namespace App\\SeviaFlow\\Resources\\UserResource\\Actions;');
    expect(file_get_contents($bulkPath))->toContain('namespace App\\SeviaFlow\\Resources\\UserResource\\BulkActions;');
});

it('generated action uses current action api', function (): void {
    pest_test_case()->artisan('seviaflow:make-action DeactivateUserAction --resource=UserResource')
        ->assertSuccessful();

    $path = sys_get_temp_dir().'/seviaflow-make-actions-tests/app/SeviaFlow/Resources/UserResource/Actions/DeactivateUserAction.php';
    $content = file_get_contents($path);

    expect($content)->toContain('use SeviaFlow\\Core\\Resources\\Action;')
        ->and($content)->toContain('Action::make(')
        ->and($content)->toContain('->requiresConfirmation()')
        ->and($content)->toContain('->successMessage(')
        ->and($content)->toContain('->errorMessage(')
        ->and($content)->toContain('->handle(function (mixed $record): void');
});

it('generated bulk action uses current bulk action api', function (): void {
    pest_test_case()->artisan('seviaflow:make-bulk-action DeactivateUsersBulkAction --resource=UserResource')
        ->assertSuccessful();

    $path = sys_get_temp_dir().'/seviaflow-make-actions-tests/app/SeviaFlow/Resources/UserResource/BulkActions/DeactivateUsersBulkAction.php';
    $content = file_get_contents($path);

    expect($content)->toContain('use SeviaFlow\\Core\\Resources\\BulkAction;')
        ->and($content)->toContain('BulkAction::make(')
        ->and($content)->toContain('->mutating()')
        ->and($content)->toContain('->requiresConfirmation()')
        ->and($content)->toContain('->successMessage(')
        ->and($content)->toContain('->errorMessage(')
        ->and($content)->toContain('->handle(function (Collection $records): void');
});

it('commands fail clearly when resource is missing', function (): void {
    pest_test_case()->artisan('seviaflow:make-action CloseOrderAction --resource=OrderResource')
        ->assertFailed();

    pest_test_case()->artisan('seviaflow:make-bulk-action CloseOrdersBulkAction --resource=OrderResource')
        ->assertFailed();
});

it('does not overwrite without force', function (): void {
    $path = sys_get_temp_dir().'/seviaflow-make-actions-tests/app/SeviaFlow/Resources/UserResource/Actions/DeactivateUserAction.php';

    pest_test_case()->artisan('seviaflow:make-action DeactivateUserAction --resource=UserResource')
        ->assertSuccessful();

    file_put_contents($path, 'old-action-content');

    pest_test_case()->artisan('seviaflow:make-action DeactivateUserAction --resource=UserResource')
        ->assertFailed();

    expect(file_get_contents($path))->toBe('old-action-content');
});

it('force overwrites', function (): void {
    $path = sys_get_temp_dir().'/seviaflow-make-actions-tests/app/SeviaFlow/Resources/UserResource/BulkActions/DeactivateUsersBulkAction.php';

    pest_test_case()->artisan('seviaflow:make-bulk-action DeactivateUsersBulkAction --resource=UserResource')
        ->assertSuccessful();

    file_put_contents($path, 'old-bulk-content');

    pest_test_case()->artisan('seviaflow:make-bulk-action DeactivateUsersBulkAction --resource=UserResource --force')
        ->assertSuccessful();

    expect(file_get_contents($path))->toContain('final class DeactivateUsersBulkAction');
    expect(file_get_contents($path) === 'old-bulk-content')->toBeFalse();
});
