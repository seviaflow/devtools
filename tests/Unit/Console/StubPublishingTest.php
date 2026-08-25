<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

beforeEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-stub-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }

    mkdir($tmp, 0755, true);

    config()->set('seviaflow.paths.resources', $tmp.'/app/SeviaFlow/Resources');
    config()->set('seviaflow.namespaces.resources', 'App\\SeviaFlow\\Resources');
});

afterEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-stub-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }

    $customStubs = base_path('stubs/seviaflow');

    if (is_dir($customStubs)) {
        File::deleteDirectory($customStubs);
    }
});

it('canonical stubs exist', function (): void {
    $packageRoot = dirname(__DIR__, 3);
    $base = $packageRoot.'/resources/stubs/seviaflow';

    expect(is_file($base.'/resource.stub'))->toBeTrue()
        ->and(is_file($base.'/table.stub'))->toBeTrue()
        ->and(is_file($base.'/form.stub'))->toBeTrue()
        ->and(is_file($base.'/section.stub'))->toBeTrue()
        ->and(is_file($base.'/action.stub'))->toBeTrue()
        ->and(is_file($base.'/bulk-action.stub'))->toBeTrue()
        ->and(is_file($base.'/workflow-handler.stub'))->toBeTrue();
});

it('stubs publish tag is registered', function (): void {
    $paths = ServiceProvider::pathsToPublish(null, 'seviaflow-stubs');

    expect($paths)->not->toBeEmpty();
    expect(array_key_first($paths))->toContain('resources/stubs/seviaflow');
    expect(array_values($paths)[0])->toContain('stubs/seviaflow');
});

it('generators prefer published custom stubs when available', function (): void {
    pest_test_case()->artisan('seviaflow:make-resource UserResource --model=User')->assertSuccessful();

    $customStubDir = base_path('stubs/seviaflow');
    File::ensureDirectoryExists($customStubDir);

    file_put_contents($customStubDir.'/action.stub', <<<'PHP'
<?php

declare(strict_types=1);

namespace {{ action_namespace }};

use SeviaFlow\Core\Resources\Action;

final class {{ action_class }}
{
    public static function make(): Action
    {
        return Action::make('{{ action_name }}')
            ->label('Custom Stub Label')
            ->handle(function (mixed $record): void {
                // custom
            });
    }
}
PHP);

    pest_test_case()->artisan('seviaflow:make-action DemoAction --resource=UserResource --force')
        ->assertSuccessful();

    $generated = sys_get_temp_dir().'/seviaflow-stub-tests/app/SeviaFlow/Resources/UserResource/Actions/DemoAction.php';

    expect(file_get_contents($generated))->toContain('Custom Stub Label')
        ->and(file_get_contents($generated))->toContain('// custom');
});
