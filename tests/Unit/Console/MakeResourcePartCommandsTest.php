<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-make-parts-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }

    mkdir($tmp, 0755, true);

    config()->set('seviaflow.paths.resources', $tmp.'/app/SeviaFlow/Resources');
    config()->set('seviaflow.namespaces.resources', 'App\\SeviaFlow\\Resources');

    pest_test_case()->artisan('seviaflow:make-resource UserResource --model=User')->assertSuccessful();
});

afterEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-make-parts-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }
});

it('make-table generates table class', function (): void {
    pest_test_case()->artisan('seviaflow:make-table UserTable --resource=UserResource --force')
        ->assertSuccessful();

    $path = sys_get_temp_dir().'/seviaflow-make-parts-tests/app/SeviaFlow/Resources/UserResource/Tables/UserTable.php';

    expect(is_file($path))->toBeTrue()
        ->and(file_get_contents($path))->toContain('namespace App\\SeviaFlow\\Resources\\UserResource\\Tables;')
        ->and(file_get_contents($path))->toContain('public static function configure(Table $table): Table');
});

it('make-form generates form class', function (): void {
    pest_test_case()->artisan('seviaflow:make-form UserForm --resource=UserResource --force')
        ->assertSuccessful();

    $path = sys_get_temp_dir().'/seviaflow-make-parts-tests/app/SeviaFlow/Resources/UserResource/Forms/UserForm.php';

    expect(is_file($path))->toBeTrue()
        ->and(file_get_contents($path))->toContain('namespace App\\SeviaFlow\\Resources\\UserResource\\Forms;')
        ->and(file_get_contents($path))->toContain('public static function configure(Form $form): Form');
});

it('make-section generates section class', function (): void {
    pest_test_case()->artisan('seviaflow:make-section PersonalDataSection --resource=UserResource')
        ->assertSuccessful();

    $path = sys_get_temp_dir().'/seviaflow-make-parts-tests/app/SeviaFlow/Resources/UserResource/Forms/Sections/PersonalDataSection.php';

    expect(is_file($path))->toBeTrue()
        ->and(file_get_contents($path))->toContain('namespace App\\SeviaFlow\\Resources\\UserResource\\Forms\\Sections;')
        ->and(file_get_contents($path))->toContain('public static function make(): Section');
});

it('fails clearly when resource is missing', function (): void {
    pest_test_case()->artisan('seviaflow:make-table OrderTable --resource=OrderResource')
        ->assertFailed();

    pest_test_case()->artisan('seviaflow:make-form OrderForm --resource=OrderResource')
        ->assertFailed();

    pest_test_case()->artisan('seviaflow:make-section OrderDetailsSection --resource=OrderResource')
        ->assertFailed();
});

it('does not overwrite without force', function (): void {
    $tablePath = sys_get_temp_dir().'/seviaflow-make-parts-tests/app/SeviaFlow/Resources/UserResource/Tables/UserTable.php';

    file_put_contents($tablePath, 'original-table-content');

    pest_test_case()->artisan('seviaflow:make-table UserTable --resource=UserResource')
        ->assertFailed();

    expect(file_get_contents($tablePath))->toBe('original-table-content');
});

it('overwrites with force', function (): void {
    $sectionPath = sys_get_temp_dir().'/seviaflow-make-parts-tests/app/SeviaFlow/Resources/UserResource/Forms/Sections/PersonalDataSection.php';

    pest_test_case()->artisan('seviaflow:make-section PersonalDataSection --resource=UserResource')
        ->assertSuccessful();

    file_put_contents($sectionPath, 'stale-section-content');

    pest_test_case()->artisan('seviaflow:make-section PersonalDataSection --resource=UserResource --force')
        ->assertSuccessful();

    expect(file_get_contents($sectionPath))->toContain('final class PersonalDataSection');
    expect(file_get_contents($sectionPath) === 'stale-section-content')->toBeFalse();
});

it('supports custom resource path and namespace', function (): void {
    $customBase = sys_get_temp_dir().'/seviaflow-make-parts-tests/custom/Resources';

    config()->set('seviaflow.paths.resources', $customBase);
    config()->set('seviaflow.namespaces.resources', 'Custom\\Admin\\Resources');

    pest_test_case()->artisan('seviaflow:make-resource ProductResource --model=Product')
        ->assertSuccessful();

    pest_test_case()->artisan('seviaflow:make-table ProductTable --resource=ProductResource --force')
        ->assertSuccessful();

    pest_test_case()->artisan('seviaflow:make-form ProductForm --resource=ProductResource --force')
        ->assertSuccessful();

    pest_test_case()->artisan('seviaflow:make-section ProductBasicsSection --resource=ProductResource')
        ->assertSuccessful();

    expect(file_get_contents($customBase.'/ProductResource/Tables/ProductTable.php'))->toContain('namespace Custom\\Admin\\Resources\\ProductResource\\Tables;');
    expect(file_get_contents($customBase.'/ProductResource/Forms/ProductForm.php'))->toContain('namespace Custom\\Admin\\Resources\\ProductResource\\Forms;');
    expect(file_get_contents($customBase.'/ProductResource/Forms/Sections/ProductBasicsSection.php'))->toContain('namespace Custom\\Admin\\Resources\\ProductResource\\Forms\\Sections;');
});
