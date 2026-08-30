<?php

declare(strict_types=1);

use SeviaFlow\DevTools\Discovery\FieldSuggestion;
use SeviaFlow\DevTools\Discovery\FilterSuggestion;
use SeviaFlow\DevTools\Discovery\Generation\DiscoveredResourceGenerator;
use SeviaFlow\DevTools\Discovery\RelationSuggestion;

it('builds extracted file map and registration entry', function (): void {
    $generator = new DiscoveredResourceGenerator;
    $map = $generator->buildFileMap(
        resourceDirectory: '/tmp/generated/UserResource',
        resourceNamespace: 'App\\SeviaFlow\\Resources\\UserResource',
        resourceClass: 'UserResource',
        resourceBase: 'User',
        modelClass: 'App\\Models\\User',
        fieldSuggestions: [new FieldSuggestion('name', 'TextField')],
        filterSuggestions: [new FilterSuggestion('is_active', 'BooleanFilter')],
        relationSuggestions: [new RelationSuggestion('orders', 'hasMany', 'method', 'App\\Models\\Order')],
        stubRenderer: static fn (string $stubName, array $replacements): string => '<?php // stub',
    );

    expect($map->registrationEntry)->toBe("'users' => App\\SeviaFlow\\Resources\\UserResource\\UserResource::class,")
        ->and($map->files)->toHaveKey('/tmp/generated/UserResource/UserResource.php')
        ->and($map->files)->toHaveKey('/tmp/generated/UserResource/Tables/UserTable.php')
        ->and($map->files)->toHaveKey('/tmp/generated/UserResource/Forms/UserForm.php')
        ->and($map->files)->toHaveKey('/tmp/generated/UserResource/Actions/.gitkeep');
});

it('builds deterministic style-clean PHP source for generated resources', function (): void {
    $generator = new DiscoveredResourceGenerator;
    $arguments = [
        'resourceDirectory' => '/tmp/generated/UserResource',
        'resourceNamespace' => 'App\\SeviaFlow\\Resources\\UserResource',
        'resourceClass' => 'UserResource',
        'resourceBase' => 'User',
        'modelClass' => 'App\\Models\\User',
        'fieldSuggestions' => [
            new FieldSuggestion('id', 'NumberField'),
            new FieldSuggestion('name', 'TextField'),
            new FieldSuggestion('is_active', 'ToggleField'),
            new FieldSuggestion('unit_cost', 'MoneyField'),
        ],
        'filterSuggestions' => [
            new FilterSuggestion('name', 'TextFilter'),
            new FilterSuggestion('is_active', 'BooleanFilter'),
            new FilterSuggestion('unit_cost', 'MoneyFilter'),
        ],
        'relationSuggestions' => [
            new RelationSuggestion('orders', 'hasMany', 'method', 'App\\Models\\Order'),
        ],
        'stubRenderer' => discoveredResourceStubRenderer(...),
    ];

    $firstMap = $generator->buildFileMap(...$arguments);
    $secondMap = $generator->buildFileMap(...$arguments);

    expect($secondMap->files)->toBe($firstMap->files);

    $resource = $firstMap->files['/tmp/generated/UserResource/UserResource.php'];
    $table = $firstMap->files['/tmp/generated/UserResource/Tables/UserTable.php'];
    $form = $firstMap->files['/tmp/generated/UserResource/Forms/UserForm.php'];

    expect($resource)
        ->toContain('use App\\Models\\User;')
        ->toContain('use App\\SeviaFlow\\Resources\\OrderResource\\OrderResource;')
        ->toContain("HasManyRelation::make('orders')->resource(OrderResource::class),");

    expect(str_contains($resource, 'resource(\\App\\SeviaFlow'))->toBeFalse();

    expect($form)
        ->toContain('use SeviaFlow\\Core\\Resources\\Fields\\MoneyField;')
        ->toContain("MoneyField::make('unit_cost')->label('Unit Cost'),");

    expect($table)
        ->toContain('use SeviaFlow\\Core\\Resources\\Columns\\BooleanColumn;')
        ->toContain('use SeviaFlow\\Core\\Resources\\Filters\\BooleanFilter;')
        ->toContain("BooleanColumn::make('is_active')->label('Is Active')->sortable(),");

    expectPhpSourceToLint($resource);
    expectPhpSourceToLint($table);
    expectPhpSourceToLint($form);
});

/**
 * @param  array<string, string>  $replacements
 */
function discoveredResourceStubRenderer(string $stubName, array $replacements): string
{
    $stub = (string) file_get_contents(__DIR__.'/../../../../resources/stubs/seviaflow/'.$stubName);

    return strtr($stub, $replacements);
}

function expectPhpSourceToLint(string $source): void
{
    $path = tempnam(sys_get_temp_dir(), 'seviaflow-generated-lint-');

    expect($path)->not->toBeFalse();

    $phpPath = $path.'.php';
    rename($path, $phpPath);
    file_put_contents($phpPath, $source);

    exec(PHP_BINARY.' -l '.escapeshellarg($phpPath), $output, $exitCode);
    unlink($phpPath);

    expect($exitCode)->toBe(0, implode("\n", $output));
}
