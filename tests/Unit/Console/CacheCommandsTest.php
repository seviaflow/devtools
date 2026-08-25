<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use SeviaFlow\Core\Resources\ApiResource;
use SeviaFlow\Core\Resources\Resource;

beforeEach(function (): void {
    config()->set('cache.default', 'array');
    config()->set('seviaflow.performance.cache.store', 'array');
    config()->set('seviaflow.performance.cache.enabled', true);
    config()->set('seviaflow.performance.cache.resources', true);
    config()->set('seviaflow.performance.cache.navigation', true);
    config()->set('seviaflow.performance.cache.openapi', true);
    config()->set('seviaflow.api.enabled', true);
    config()->set('seviaflow.resources', [
        'cache-resource' => CacheCommandResource::class,
    ]);

    flushArrayCache();

    if (! Schema::hasTable('cache_command_records')) {
        Schema::create('cache_command_records', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }
});

it('cache command exists', function (): void {
    $commands = array_keys(app(ConsoleKernel::class)->all());

    expect($commands)->toContain('seviaflow:cache');
});

it('cache-clear command exists', function (): void {
    $commands = array_keys(app(ConsoleKernel::class)->all());

    expect($commands)->toContain('seviaflow:cache-clear');
});

it('cache command warms resource metadata cache', function (): void {
    pest_test_case()->artisan('seviaflow:cache')
        ->expectsOutput('✓ Resource metadata cached')
        ->assertSuccessful();

    $cacheKey = sprintf('seviaflow:resource-metadata:%s:%s', 'cache-resource', sha1(CacheCommandResource::class));

    expect(cache()->store('array')->get($cacheKey))->toBeArray();
});

it('cache command warms navigation cache', function (): void {
    pest_test_case()->artisan('seviaflow:cache')
        ->expectsOutput('✓ Navigation metadata cached')
        ->assertSuccessful();

    $cached = cache()->store('array')->get('seviaflow.navigation.base');

    expect($cached)->toBeArray()
        ->and($cached)->toHaveKey('groups');
});

it('cache command warms openapi cache', function (): void {
    pest_test_case()->artisan('seviaflow:cache')
        ->expectsOutput('✓ OpenAPI schema cached')
        ->assertSuccessful();

    $cached = cache()->store('array')->get('seviaflow.openapi');

    expect($cached)->toBeArray()
        ->and($cached)->toHaveKey('openapi')
        ->and($cached)->toHaveKey('paths');
});

it('cache-clear clears all caches', function (): void {
    pest_test_case()->artisan('seviaflow:cache')->assertSuccessful();

    pest_test_case()->artisan('seviaflow:cache-clear')
        ->expectsOutput('✓ Resource metadata cache cleared')
        ->expectsOutput('✓ Navigation metadata cache cleared')
        ->expectsOutput('✓ OpenAPI cache cleared')
        ->expectsOutput('✓ Dashboard summary cache cleared')
        ->expectsOutput('✓ Dashboard widget cache cleared')
        ->assertSuccessful();

    $resourceCacheKey = sprintf('seviaflow:resource-metadata:%s:%s', 'cache-resource', sha1(CacheCommandResource::class));

    expect(cache()->store('array')->get($resourceCacheKey))->toBeNull()
        ->and(cache()->store('array')->get('seviaflow.navigation.base'))->toBeNull()
        ->and(cache()->store('array')->get('seviaflow.openapi'))->toBeNull();
});

it('cache command outputs useful skip messages when cache is disabled globally', function (): void {
    config()->set('seviaflow.performance.cache.enabled', false);

    pest_test_case()->artisan('seviaflow:cache')
        ->expectsOutput('- Cache is globally disabled (`performance.cache.enabled=false`), skipping warm.')
        ->assertSuccessful();
});

it('disabled cache flags skip warming but clear still works', function (): void {
    config()->set('seviaflow.performance.cache.resources', false);
    config()->set('seviaflow.performance.cache.navigation', false);
    config()->set('seviaflow.performance.cache.openapi', false);
    config()->set('seviaflow.performance.cache.dashboard', false);

    pest_test_case()->artisan('seviaflow:cache')
        ->expectsOutput('- Resource metadata cache skipped (disabled)')
        ->expectsOutput('- Navigation metadata cache skipped (disabled)')
        ->expectsOutput('- OpenAPI cache skipped (disabled)')
        ->expectsOutput('- Dashboard cache skipped (disabled)')
        ->assertSuccessful();

    cache()->store('array')->put('seviaflow.navigation.base', ['tmp' => true], 3600);
    cache()->store('array')->put('seviaflow.openapi', ['tmp' => true], 3600);
    cache()->store('array')->put('seviaflow.dashboard.index', ['seviaflow.dashboard.resource_overview.tmp'], 3600);
    cache()->store('array')->put('seviaflow.dashboard.resource_overview.tmp', ['tmp' => true], 3600);
    cache()->store('array')->put('seviaflow.dashboard.widgets.index', ['seviaflow.dashboard.widget.stats.tmp'], 3600);
    cache()->store('array')->put('seviaflow.dashboard.widget.stats.tmp', ['tmp' => true], 3600);

    $resourceCacheKey = sprintf('seviaflow:resource-metadata:%s:%s', 'cache-resource', sha1(CacheCommandResource::class));
    cache()->store('array')->put($resourceCacheKey, ['tmp' => true], 3600);

    pest_test_case()->artisan('seviaflow:cache-clear')->assertSuccessful();

    expect(cache()->store('array')->get($resourceCacheKey))->toBeNull()
        ->and(cache()->store('array')->get('seviaflow.navigation.base'))->toBeNull()
        ->and(cache()->store('array')->get('seviaflow.openapi'))->toBeNull()
        ->and(cache()->store('array')->get('seviaflow.dashboard.index'))->toBeNull()
        ->and(cache()->store('array')->get('seviaflow.dashboard.resource_overview.tmp'))->toBeNull()
        ->and(cache()->store('array')->get('seviaflow.dashboard.widgets.index'))->toBeNull()
        ->and(cache()->store('array')->get('seviaflow.dashboard.widget.stats.tmp'))->toBeNull();
});

it('cache warm handles api disabled safely', function (): void {
    config()->set('seviaflow.api.enabled', false);

    pest_test_case()->artisan('seviaflow:cache')
        ->expectsOutput('✓ OpenAPI schema cached')
        ->assertSuccessful();

    $cached = cache()->store('array')->get('seviaflow.openapi');

    expect($cached)->toBeNull();
});

it('cache command reports dashboard cache as user-scoped when enabled', function (): void {
    config()->set('seviaflow.performance.cache.dashboard', true);

    pest_test_case()->artisan('seviaflow:cache')
        ->expectsOutput('- Dashboard cache enabled: skipped warm (user-scoped, warmed on request)')
        ->assertSuccessful();
});

final class CacheCommandResource extends Resource
{
    protected static string $model = CacheCommandRecord::class;

    public static function api(ApiResource $api): ApiResource
    {
        return $api
            ->enabled()
            ->readable(['id', 'name']);
    }
}

final class CacheCommandRecord extends Model
{
    protected $table = 'cache_command_records';

    protected $guarded = [];
}
