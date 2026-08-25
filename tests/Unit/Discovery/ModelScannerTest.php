<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use SeviaFlow\DevTools\Discovery\ModelScanner;

beforeEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-model-scanner-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }

    mkdir($tmp, 0755, true);
    mkdir($tmp.'/Models', 0755, true);

    config()->set('seviaflow-devtools.discovery.enabled', true);
    config()->set('seviaflow-devtools.discovery.model_paths', [$tmp.'/Models']);
    config()->set('seviaflow-devtools.discovery.model_namespaces', ['Tmp\\Models']);
    config()->set('seviaflow-devtools.discovery.ignored_models', []);
});

afterEach(function (): void {
    $tmp = sys_get_temp_dir().'/seviaflow-model-scanner-tests';

    if (is_dir($tmp)) {
        File::deleteDirectory($tmp);
    }
});

it('discovers eloquent models from configured paths', function (): void {
    writeScannerModelFile('Product.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'price'];
    protected $guarded = [];
    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean'];
}
PHP);

    writeScannerModelFile('NotModel.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
final class NotModel
{
}
PHP);

    $results = (new ModelScanner)->scan();

    expect($results)->toHaveCount(1)
        ->and($results[0]->className())->toBe('Tmp\\Models\\Product')
        ->and($results[0]->shortName())->toBe('Product')
        ->and($results[0]->tableName())->toBe('products')
        ->and($results[0]->primaryKey())->toBe('id')
        ->and($results[0]->fillable())->toBe(['name', 'price'])
        ->and($results[0]->guarded())->toBe([])
        ->and($results[0]->casts())->toBe(['price' => 'decimal:2', 'is_active' => 'boolean']);
});

it('respects ignored models config', function (): void {
    writeScannerModelFile('IgnoredModel.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class IgnoredModel extends Model
{
}
PHP);

    config()->set('seviaflow-devtools.discovery.ignored_models', ['Tmp\\Models\\IgnoredModel']);

    $results = (new ModelScanner)->scan();

    expect($results)->toBe([]);
});

it('detects soft deletes usage', function (): void {
    writeScannerModelFile('SoftPost.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class SoftPost extends Model
{
    use SoftDeletes;
}
PHP);

    $results = (new ModelScanner)->scan();

    expect($results)->toHaveCount(1)
        ->and($results[0]->usesSoftDeletes())->toBeTrue();
});

it('scans safely without database records or queries', function (): void {
    writeScannerModelFile('AuditEntry.php', <<<'PHP'
<?php
declare(strict_types=1);
namespace Tmp\Models;
use Illuminate\Database\Eloquent\Model;
final class AuditEntry extends Model
{
    protected $guarded = ['*'];
}
PHP);

    $results = (new ModelScanner)->scan();

    expect($results)->toHaveCount(1)
        ->and($results[0]->className())->toBe('Tmp\\Models\\AuditEntry')
        ->and($results[0]->tableName())->toBeNull()
        ->and($results[0]->primaryKey())->toBeNull()
        ->and($results[0]->guarded())->toBe(['*']);
});

/**
 * @param  non-empty-string  $filename
 */
function writeScannerModelFile(string $filename, string $contents): void
{
    $base = sys_get_temp_dir().'/seviaflow-model-scanner-tests/Models';
    file_put_contents($base.'/'.$filename, $contents);
}
