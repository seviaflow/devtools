<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use SeviaFlow\DevTools\Discovery\FieldSuggestionService;
use SeviaFlow\DevTools\Discovery\FilterSuggestionService;
use SeviaFlow\DevTools\Discovery\ModelScanner;
use SeviaFlow\DevTools\Discovery\RelationSuggestionService;
use SeviaFlow\DevTools\Discovery\SchemaAnalyzer;
use SeviaFlow\DevTools\Discovery\WorkflowCandidateDetector;

beforeEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-discovery-suggestions-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }

    mkdir($tmp, 0755, true);
    mkdir($tmp.'/Models', 0755, true);

    config()->set('seviaflow-devtools.discovery.enabled', true);
    config()->set('seviaflow-devtools.discovery.model_paths', [$tmp.'/Models']);
    config()->set('seviaflow-devtools.discovery.model_namespaces', ['TmpDiscovery\\Models']);
    config()->set('seviaflow-devtools.discovery.ignored_models', []);

    Schema::create('discovery_users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });

    Schema::create('discovery_orders', function (Blueprint $table): void {
        $table->id();
        $table->string('email');
        $table->string('password');
        $table->text('description');
        $table->boolean('is_active')->default(true);
        $table->integer('quantity')->default(0);
        $table->decimal('price', 10, 2)->default(0);
        $table->date('order_date')->nullable();
        $table->dateTime('published_at')->nullable();
        $table->time('opening_time')->nullable();
        $table->string('website')->nullable();
        $table->string('phone')->nullable();
        $table->string('slug')->nullable();
        $table->string('avatar_image')->nullable();
        $table->string('invoice_file')->nullable();
        $table->string('status')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->string('owner_type')->nullable();
        $table->unsignedBigInteger('owner_id')->nullable();
        $table->timestamps();
    });

    Schema::create('discovery_tags', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });

    Schema::create('discovery_invoices', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('company_id')->nullable();
        $table->timestamps();
    });
});

afterEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-discovery-suggestions-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }
});

it('suggests fields and filters from schema columns', function (): void {
    writeDiscoveryModel('Order.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace TmpDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
final class Order extends Model
{
    protected $table = 'discovery_orders';
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(Tag::class);
    }
    public function profile(): HasOne
    {
        return $this->hasOne(User::class);
    }
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
    public function comments(): MorphMany
    {
        return $this->morphMany(Tag::class, 'commentable');
    }
    public function avatar(): MorphOne
    {
        return $this->morphOne(Tag::class, 'imageable');
    }
    public function labels(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'labelable');
    }
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
PHP);

    writeDiscoveryModel('User.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace TmpDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
final class User extends Model
{
    protected $table = 'discovery_users';
}
PHP);

    writeDiscoveryModel('Tag.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace TmpDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
final class Tag extends Model
{
    protected $table = 'discovery_tags';
}
PHP);

    $models = (new ModelScanner)->scan();
    $order = collect($models)->first(fn ($model): bool => $model->className() === 'TmpDiscovery\\Models\\Order');
    expect($order)->not->toBeNull();

    $columns = (new SchemaAnalyzer)->analyze($order);
    $fieldSuggestions = (new FieldSuggestionService)->suggest($columns);
    $filterSuggestions = (new FilterSuggestionService)->suggest($fieldSuggestions);

    $fieldMap = [];
    foreach ($fieldSuggestions as $field) {
        $fieldMap[$field->column()] = $field->fieldClass();
    }

    $filterMap = [];
    foreach ($filterSuggestions as $filter) {
        $filterMap[$filter->column()] = $filter->filterClass();
    }

    expect($fieldMap['email'])->toBe('EmailField')
        ->and($fieldMap['password'])->toBe('PasswordField')
        ->and($fieldMap['description'])->toBe('TextareaField')
        ->and($fieldMap['is_active'])->toBe('NumberField')
        ->and($fieldMap['quantity'])->toBe('NumberField')
        ->and($fieldMap['price'])->toBe('MoneyField')
        ->and($fieldMap['order_date'])->toBe('DateField')
        ->and($fieldMap['published_at'])->toBe('DateTimeField')
        ->and($fieldMap['opening_time'])->toBe('TimeField')
        ->and($fieldMap['website'])->toBe('UrlField')
        ->and($fieldMap['phone'])->toBe('PhoneField')
        ->and($fieldMap['slug'])->toBe('SlugField')
        ->and($fieldMap['avatar_image'])->toBe('ImageField')
        ->and($fieldMap['invoice_file'])->toBe('FileField');

    expect($filterMap['is_active'])->toBe('NumberFilter')
        ->and($filterMap['quantity'])->toBe('NumberFilter')
        ->and($filterMap['price'])->toBe('MoneyFilter')
        ->and($filterMap['order_date'])->toBe('DateFilter')
        ->and($filterMap['published_at'])->toBe('DateTimeRangeFilter')
        ->and($filterMap['opening_time'])->toBe('TimeFilter');
});

it('suggests relations from typed methods and foreign-key columns', function (): void {
    writeDiscoveryModel('Order.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace TmpDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
final class Order extends Model
{
    protected $table = 'discovery_orders';
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(Tag::class);
    }
    public function profile(): HasOne
    {
        return $this->hasOne(User::class);
    }
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
    public function comments(): MorphMany
    {
        return $this->morphMany(Tag::class, 'commentable');
    }
    public function avatar(): MorphOne
    {
        return $this->morphOne(Tag::class, 'imageable');
    }
    public function labels(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'labelable');
    }
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
PHP);

    writeDiscoveryModel('User.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace TmpDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
final class User extends Model
{
    protected $table = 'discovery_users';
}
PHP);

    writeDiscoveryModel('Tag.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace TmpDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
final class Tag extends Model
{
    protected $table = 'discovery_tags';
}
PHP);

    $models = (new ModelScanner)->scan();
    $order = collect($models)->first(fn ($model): bool => $model->className() === 'TmpDiscovery\\Models\\Order');
    $columns = (new SchemaAnalyzer)->analyze($order);

    $relations = (new RelationSuggestionService)->suggest($order, $columns);
    $map = [];
    foreach ($relations as $relation) {
        $map[$relation->name()] = [$relation->relationType(), $relation->source()];
    }

    expect($map['user'][0])->toBe('belongsTo')
        ->and($map['user'][1])->toBe('method')
        ->and($map['items'][0])->toBe('hasMany')
        ->and($map['profile'][0])->toBe('hasOne')
        ->and($map['tags'][0])->toBe('belongsToMany')
        ->and($map['comments'][0])->toBe('morphMany')
        ->and($map['avatar'][0])->toBe('morphOne')
        ->and($map['labels'][0])->toBe('morphToMany')
        ->and($map['owner'][0])->toBe('morphTo');
});

it('suggests belongsTo relation from foreign key column when method is not declared', function (): void {
    writeDiscoveryModel('Invoice.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace TmpDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
final class Invoice extends Model
{
    protected $table = 'discovery_invoices';
}
PHP);

    $invoice = collect((new ModelScanner)->scan())
        ->first(fn ($model): bool => $model->className() === 'TmpDiscovery\\Models\\Invoice');

    expect($invoice)->not->toBeNull();

    $columns = (new SchemaAnalyzer)->analyze($invoice);
    $relations = (new RelationSuggestionService)->suggest($invoice, $columns);
    $map = [];

    foreach ($relations as $relation) {
        $map[$relation->name()] = [$relation->relationType(), $relation->source()];
    }

    expect($map)->toHaveKey('company')
        ->and($map['company'][0])->toBe('belongsTo')
        ->and($map['company'][1])->toBe('column');
});

it('suggests morphTo relation from type and id columns when method is not declared', function (): void {
    writeDiscoveryModel('PlainOrder.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace TmpDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
final class PlainOrder extends Model
{
    protected $table = 'discovery_orders';
}
PHP);

    $order = collect((new ModelScanner)->scan())
        ->first(fn ($model): bool => $model->className() === 'TmpDiscovery\\Models\\PlainOrder');

    expect($order)->not->toBeNull();

    $columns = (new SchemaAnalyzer)->analyze($order);
    $relations = (new RelationSuggestionService)->suggest($order, $columns);
    $map = [];

    foreach ($relations as $relation) {
        $map[$relation->name()] = [$relation->relationType(), $relation->source()];
    }

    expect($map)->toHaveKey('owner')
        ->and($map['owner'][0])->toBe('morphTo')
        ->and($map['owner'][1])->toBe('column');
});

it('detects workflow candidate columns', function (): void {
    writeDiscoveryModel('Order.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace TmpDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
final class Order extends Model
{
    protected $table = 'discovery_orders';
}
PHP);

    $order = collect((new ModelScanner)->scan())->first();
    $columns = (new SchemaAnalyzer)->analyze($order);

    $candidates = (new WorkflowCandidateDetector)->detect($columns);
    $columnsDetected = array_map(static fn ($candidate): string => $candidate->column(), $candidates);

    expect($candidates)->not->toBeEmpty()
        ->and($columnsDetected)->toContain('status');
});

it('fails gracefully when schema table is unavailable', function (): void {
    writeDiscoveryModel('Ghost.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace TmpDiscovery\Models;
use Illuminate\Database\Eloquent\Model;
final class Ghost extends Model
{
    protected $table = 'missing_discovery_table';
}
PHP);

    $ghost = collect((new ModelScanner)->scan())->first();
    $columns = (new SchemaAnalyzer)->analyze($ghost);

    expect($columns)->toBe([]);
});

/**
 * @param  non-empty-string  $filename
 */
function writeDiscoveryModel(string $filename, string $contents): void
{
    $base = sys_get_temp_dir().'/seviaflow-discovery-suggestions-tests/Models';
    file_put_contents($base.'/'.$filename, $contents);
}
