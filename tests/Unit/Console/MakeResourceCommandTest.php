<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-make-resource-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }

    mkdir($tmp, 0755, true);

    config()->set('seviaflow.paths.resources', $tmp.'/app/SeviaFlow/Resources');
    config()->set('seviaflow.namespaces.resources', 'App\\SeviaFlow\\Resources');
});

afterEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-make-resource-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }
});

it('make-resource command exists', function (): void {
    $commands = array_keys(app(ConsoleKernel::class)->all());

    expect($commands)->toContain('seviaflow:make-resource');
});

it('generates extracted resource directory structure and files', function (): void {
    pest_test_case()->artisan('seviaflow:make-resource UserResource --model=User')
        ->assertSuccessful();

    $base = sys_get_temp_dir().'/seviaflow-make-resource-tests/app/SeviaFlow/Resources/UserResource';

    expect(is_dir($base))->toBeTrue()
        ->and(is_file($base.'/UserResource.php'))->toBeTrue()
        ->and(is_file($base.'/Tables/UserTable.php'))->toBeTrue()
        ->and(is_file($base.'/Forms/UserForm.php'))->toBeTrue()
        ->and(is_file($base.'/Actions/.gitkeep'))->toBeTrue()
        ->and(is_file($base.'/BulkActions/.gitkeep'))->toBeTrue()
        ->and(is_file($base.'/Relations/.gitkeep'))->toBeTrue()
        ->and(is_file($base.'/Workflows/.gitkeep'))->toBeTrue();
});

it('generated resource delegates to extracted classes', function (): void {
    pest_test_case()->artisan('seviaflow:make-resource UserResource --model=User')
        ->assertSuccessful();

    $resourceFile = sys_get_temp_dir().'/seviaflow-make-resource-tests/app/SeviaFlow/Resources/UserResource/UserResource.php';
    $content = file_get_contents($resourceFile);

    expect($content)->toContain('return UserTable::configure($table);')
        ->and($content)->toContain('return UserForm::configure($form);')
        ->and($content)->toContain('final class UserResource extends Resource');
});

it('does not overwrite files without force', function (): void {
    $base = sys_get_temp_dir().'/seviaflow-make-resource-tests/app/SeviaFlow/Resources/UserResource';

    pest_test_case()->artisan('seviaflow:make-resource UserResource --model=User')
        ->assertSuccessful();

    file_put_contents($base.'/UserResource.php', 'original-content');

    pest_test_case()->artisan('seviaflow:make-resource UserResource --model=User')
        ->assertFailed();

    expect(file_get_contents($base.'/UserResource.php'))->toBe('original-content');
});

it('overwrites files with force', function (): void {
    $base = sys_get_temp_dir().'/seviaflow-make-resource-tests/app/SeviaFlow/Resources/UserResource';

    pest_test_case()->artisan('seviaflow:make-resource UserResource --model=User')
        ->assertSuccessful();

    file_put_contents($base.'/UserResource.php', 'stale-content');

    pest_test_case()->artisan('seviaflow:make-resource UserResource --model=User --force')
        ->assertSuccessful();

    expect(file_get_contents($base.'/UserResource.php'))->toContain('final class UserResource extends Resource');
    expect(file_get_contents($base.'/UserResource.php') === 'stale-content')->toBeFalse();
});

it('supports custom path and namespace config', function (): void {
    config()->set('seviaflow.paths.resources', sys_get_temp_dir().'/seviaflow-make-resource-tests/custom/Resources');
    config()->set('seviaflow.namespaces.resources', 'Custom\\Admin\\Resources');

    pest_test_case()->artisan('seviaflow:make-resource ProductResource --model=Product')
        ->assertSuccessful();

    $resourceFile = sys_get_temp_dir().'/seviaflow-make-resource-tests/custom/Resources/ProductResource/ProductResource.php';

    expect(is_file($resourceFile))->toBeTrue();
    expect(file_get_contents($resourceFile))->toContain('namespace Custom\\Admin\\Resources\\ProductResource;');
});

it('resolves short model name to app models namespace', function (): void {
    pest_test_case()->artisan('seviaflow:make-resource UserResource --model=User')
        ->assertSuccessful();

    $resourceFile = sys_get_temp_dir().'/seviaflow-make-resource-tests/app/SeviaFlow/Resources/UserResource/UserResource.php';

    expect(file_get_contents($resourceFile))->toContain('use App\\Models\\User;')
        ->and(file_get_contents($resourceFile))->toContain('protected static string $model = User::class;');
});

it('supports full model class', function (): void {
    pest_test_case()->artisan('seviaflow:make-resource', [
        'name' => 'ExampleUserResource',
        '--model' => 'Tests\\Fixtures\\Models\\ExampleUser',
    ])
        ->assertSuccessful();

    $resourceFile = sys_get_temp_dir().'/seviaflow-make-resource-tests/app/SeviaFlow/Resources/ExampleUserResource/ExampleUserResource.php';

    expect(file_get_contents($resourceFile))->toContain('use Tests\\Fixtures\\Models\\ExampleUser;')
        ->and(file_get_contents($resourceFile))->toContain('protected static string $model = ExampleUser::class;');
});
