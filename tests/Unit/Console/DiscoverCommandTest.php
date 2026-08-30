<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-discover-command-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }

    mkdir($tmp, 0755, true);
    mkdir($tmp.'/Models', 0755, true);
    mkdir($tmp.'/generated', 0755, true);

    config()->set('seviaflow-devtools.discovery.enabled', true);
    config()->set('seviaflow-devtools.discovery.model_paths', [$tmp.'/Models']);
    config()->set('seviaflow-devtools.discovery.model_namespaces', ['Tmp\\Models']);
    config()->set('seviaflow-devtools.discovery.ignored_models', []);
    config()->set('seviaflow.paths.resources', $tmp.'/generated');

    Schema::create('discover_customers', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
    });

    Schema::create('discover_items', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('order_id')->nullable();
    });

    Schema::create('discover_orders', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('customer_id')->nullable();
    });

    Schema::create('discover_lines', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('invoice_id')->nullable();
    });

    Schema::create('discover_invoices', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('company_id')->nullable();
    });
});

afterEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-discover-command-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }
});

it('discover command exists', function (): void {
    $commands = array_keys(app(ConsoleKernel::class)->all());

    expect($commands)->toContain('seviaflow:discover');
});

it('uses dry-run mode by default and does not generate files', function (): void {
    writeModelFile('User.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class User extends Model
{
}
PHP);

    pest_test_case()->artisan('seviaflow:discover')
        ->expectsOutput('Dry-run is the default mode. Use --generate to write files.')
        ->expectsOutput('Discovered models:')
        ->expectsOutput('- Tmp\\Models\\User')
        ->expectsOutput('  Suggested resource: UserResource')
        ->assertSuccessful();

    expect(File::allFiles(sys_get_temp_dir().'/seviaflow-discover-command-tests/generated'))->toHaveCount(0);
});

it('handles no models found', function (): void {
    pest_test_case()->artisan('seviaflow:discover')
        ->expectsOutput('Dry-run is the default mode. Use --generate to write files.')
        ->expectsOutput('No discoverable Eloquent models found.')
        ->assertSuccessful();
});

it('respects ignored models option', function (): void {
    writeModelFile('User.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class User extends Model
{
}
PHP);

    pest_test_case()->artisan('seviaflow:discover', [
        '--ignored' => 'Tmp\\Models\\User',
    ])
        ->expectsOutput('Dry-run is the default mode. Use --generate to write files.')
        ->expectsOutput('No discoverable Eloquent models found.')
        ->assertSuccessful();
});

it('shows schema warning when schema data is unavailable', function (): void {
    writeModelFile('Order.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class Order extends Model
{
    protected $table = 'missing_orders_table';
}
PHP);

    pest_test_case()->artisan('seviaflow:discover --with-schema')
        ->expectsOutput('Dry-run is the default mode. Use --generate to write files.')
        ->expectsOutput('Discovered models:')
        ->expectsOutput('- Tmp\\Models\\Order')
        ->expectsOutput('  Suggested resource: OrderResource')
        ->expectsOutput('  Schema suggestions unavailable for Tmp\\Models\\Order (missing table or schema access).')
        ->assertSuccessful();
});

it('generates extracted resource structure only with generate option', function (): void {
    writeModelFile('User.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class User extends Model
{
}
PHP);

    $base = sys_get_temp_dir().'/seviaflow-discover-command-tests/generated/UserResource';

    pest_test_case()->artisan('seviaflow:discover --generate')
        ->expectsOutput('Generation mode enabled.')
        ->expectsOutput('Discovered models:')
        ->expectsOutput('- Tmp\\Models\\User')
        ->expectsOutput('Config snippet (add manually to seviaflow.resources):')
        ->expectsOutput("    'users' => App\\SeviaFlow\\Resources\\UserResource\\UserResource::class,")
        ->assertSuccessful();

    expect(is_file($base.'/UserResource.php'))->toBeTrue()
        ->and(is_file($base.'/Tables/UserTable.php'))->toBeTrue()
        ->and(is_file($base.'/Forms/UserForm.php'))->toBeTrue()
        ->and(is_file($base.'/Actions/.gitkeep'))->toBeTrue()
        ->and(is_file($base.'/BulkActions/.gitkeep'))->toBeTrue()
        ->and(is_file($base.'/Relations/.gitkeep'))->toBeTrue()
        ->and(is_file($base.'/Workflows/.gitkeep'))->toBeTrue();
});

it('does not overwrite generated files without force', function (): void {
    writeModelFile('User.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class User extends Model
{
}
PHP);

    $base = sys_get_temp_dir().'/seviaflow-discover-command-tests/generated/UserResource';

    pest_test_case()->artisan('seviaflow:discover --generate')
        ->assertSuccessful();

    file_put_contents($base.'/UserResource.php', 'custom-content');

    pest_test_case()->artisan('seviaflow:discover --generate')
        ->assertFailed();

    expect(file_get_contents($base.'/UserResource.php'))->toBe('custom-content');
});

it('overwrites generated files with force', function (): void {
    writeModelFile('User.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class User extends Model
{
}
PHP);

    $base = sys_get_temp_dir().'/seviaflow-discover-command-tests/generated/UserResource';

    pest_test_case()->artisan('seviaflow:discover --generate')
        ->assertSuccessful();

    file_put_contents($base.'/UserResource.php', 'custom-content');

    pest_test_case()->artisan('seviaflow:discover --generate --force')
        ->assertSuccessful();

    expect(file_get_contents($base.'/UserResource.php'))
        ->toContain('final class UserResource extends Resource');
    expect(file_get_contents($base.'/UserResource.php') === 'custom-content')->toBeFalse();
});

it('dry-run reports discovered relation suggestions', function (): void {
    writeModelFile('RelationOrder.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class RelationOrder extends Model
{
    protected $table = 'discover_orders';
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
PHP);

    writeModelFile('RelationCustomer.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class RelationCustomer extends Model
{
    protected $table = 'discover_customers';
}
PHP);

    writeModelFile('RelationItem.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class RelationItem extends Model
{
    protected $table = 'discover_items';
}
PHP);

    $exitCode = Artisan::call('seviaflow:discover', [
        '--with-schema' => true,
    ]);

    expect($exitCode)->toBe(0);

    $output = Artisan::output();

    expect($output)
        ->toContain('Relation suggestions:')
        ->toContain('customer:belongsTo Customer (method)')
        ->toContain('items:hasMany Item (method)');
});

it('generated resource includes high-confidence relation definitions and skips low-confidence ones', function (): void {
    writeModelFile('Invoice.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class Invoice extends Model
{
    protected $table = 'discover_invoices';
    public function lines(): HasMany
    {
        return $this->hasMany(Line::class);
    }
}
PHP);

    writeModelFile('Line.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class Line extends Model
{
    protected $table = 'discover_lines';
}
PHP);

    $base = sys_get_temp_dir().'/seviaflow-discover-command-tests/generated/InvoiceResource';

    pest_test_case()->artisan('seviaflow:discover --generate --with-schema')
        ->assertSuccessful();

    $resourceFile = $base.'/InvoiceResource.php';
    expect(is_file($resourceFile))->toBeTrue();

    $contents = (string) file_get_contents($resourceFile);

    expect($contents)
        ->toContain('use App\\SeviaFlow\\Resources\\LineResource\\LineResource;')
        ->toContain("HasManyRelation::make('lines')->resource(LineResource::class),");
    expect(str_contains($contents, "BelongsToRelation::make('company')"))->toBeFalse();
});

/**
 * @param  non-empty-string  $filename
 */
function writeModelFile(string $filename, string $contents): void
{
    $base = sys_get_temp_dir().'/seviaflow-discover-command-tests/Models';
    file_put_contents($base.'/'.$filename, $contents);
}
