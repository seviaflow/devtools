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
