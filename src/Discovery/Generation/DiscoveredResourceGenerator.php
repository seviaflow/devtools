<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery\Generation;

use Illuminate\Support\Str;
use SeviaFlow\DevTools\Discovery\FieldSuggestion;
use SeviaFlow\DevTools\Discovery\FilterSuggestion;
use SeviaFlow\DevTools\Discovery\RelationSuggestion;

final class DiscoveredResourceGenerator
{
    public function __construct(
        private readonly ResourceRegistrationSnippet $registrationSnippet = new ResourceRegistrationSnippet
    ) {}

    /**
     * @param  array<int, FieldSuggestion>  $fieldSuggestions
     * @param  array<int, FilterSuggestion>  $filterSuggestions
     * @param  array<int, RelationSuggestion>  $relationSuggestions
     * @param  callable(string, array<string, string>):string  $stubRenderer
     */
    public function buildFileMap(
        string $resourceDirectory,
        string $resourceNamespace,
        string $resourceClass,
        string $resourceBase,
        string $modelClass,
        array $fieldSuggestions,
        array $filterSuggestions,
        array $relationSuggestions,
        callable $stubRenderer
    ): GeneratedResourceFileMap {
        $tableClass = $resourceBase.'Table';
        $formClass = $resourceBase.'Form';

        $files = [
            $resourceDirectory.DIRECTORY_SEPARATOR.$resourceClass.'.php' => $stubRenderer('resource.stub', [
                '{{ resource_namespace }}' => $resourceNamespace,
                '{{ resource_class }}' => $resourceClass,
                '{{ imports }}' => $this->renderResourceImports($resourceNamespace, $modelClass, $tableClass, $formClass, $relationSuggestions),
                '{{ table_class_short }}' => $tableClass,
                '{{ form_class_short }}' => $formClass,
                '{{ model_short }}' => class_basename($modelClass),
                '{{ relation_lines }}' => $this->renderRelationLines($relationSuggestions),
            ]),
            $resourceDirectory.DIRECTORY_SEPARATOR.'Tables'.DIRECTORY_SEPARATOR.$tableClass.'.php' => $this->renderGeneratedTable(
                $resourceNamespace.'\\Tables',
                $tableClass,
                $fieldSuggestions,
                $filterSuggestions
            ),
            $resourceDirectory.DIRECTORY_SEPARATOR.'Forms'.DIRECTORY_SEPARATOR.$formClass.'.php' => $this->renderGeneratedForm(
                $resourceNamespace.'\\Forms',
                $formClass,
                $fieldSuggestions
            ),
            $resourceDirectory.DIRECTORY_SEPARATOR.'Actions'.DIRECTORY_SEPARATOR.'.gitkeep' => '',
            $resourceDirectory.DIRECTORY_SEPARATOR.'BulkActions'.DIRECTORY_SEPARATOR.'.gitkeep' => '',
            $resourceDirectory.DIRECTORY_SEPARATOR.'Relations'.DIRECTORY_SEPARATOR.'.gitkeep' => '',
            $resourceDirectory.DIRECTORY_SEPARATOR.'Workflows'.DIRECTORY_SEPARATOR.'.gitkeep' => '',
        ];

        return new GeneratedResourceFileMap(
            files: $files,
            registrationEntry: $this->registrationSnippet->makeEntry($resourceBase, $resourceNamespace, $resourceClass)
        );
    }

    /**
     * @param  array<string, string>  $fileMap
     */
    public function hasCollisions(array $fileMap): bool
    {
        foreach (array_keys($fileMap) as $path) {
            if (is_file($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, FieldSuggestion>  $fieldSuggestions
     * @param  array<int, FilterSuggestion>  $filterSuggestions
     */
    private function renderGeneratedTable(string $namespace, string $className, array $fieldSuggestions, array $filterSuggestions): string
    {
        $columnLines = $this->buildColumnLines($fieldSuggestions);
        $filterLines = $this->buildFilterLines($filterSuggestions);
        $imports = array_merge(
            ['SeviaFlow\\Core\\Resources\\Table'],
            $this->columnImports($fieldSuggestions),
            $this->filterImports($filterSuggestions)
        );
        $imports = array_values(array_unique($imports));
        $this->sortImports($imports);

        $body = "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\n";
        foreach ($imports as $import) {
            $body .= "use {$import};\n";
        }

        $body .= "\nfinal class {$className}\n{\n";
        $body .= "    public static function configure(Table \$table): Table\n    {\n";
        $body .= "        return \$table\n            ->columns([\n";
        foreach ($columnLines as $line) {
            $body .= "                {$line}\n";
        }
        $body .= "            ])\n";
        $body .= "            ->filters([\n";
        foreach ($filterLines as $line) {
            $body .= "                {$line}\n";
        }
        $body .= "            ]);\n";
        $body .= "    }\n}\n";

        return $body;
    }

    /**
     * @param  array<int, FieldSuggestion>  $fieldSuggestions
     */
    private function renderGeneratedForm(string $namespace, string $className, array $fieldSuggestions): string
    {
        $fieldLines = $this->buildFormFieldLines($fieldSuggestions);
        $imports = array_merge(
            ['SeviaFlow\\Core\\Resources\\Form'],
            $this->formImports($fieldSuggestions)
        );
        $imports = array_values(array_unique($imports));
        $this->sortImports($imports);

        $body = "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\n";
        foreach ($imports as $import) {
            $body .= "use {$import};\n";
        }

        $body .= "\nfinal class {$className}\n{\n";
        $body .= "    public static function configure(Form \$form): Form\n    {\n";
        $body .= "        return \$form->fields([\n";
        foreach ($fieldLines as $line) {
            $body .= "            {$line}\n";
        }
        $body .= "        ]);\n";
        $body .= "    }\n}\n";

        return $body;
    }

    /**
     * @param  array<int, FieldSuggestion>  $fieldSuggestions
     * @return array<int, string>
     */
    private function buildColumnLines(array $fieldSuggestions): array
    {
        if ($fieldSuggestions === []) {
            return ["TextColumn::make('id')->label('ID')->sortable(),"];
        }

        $lines = [];
        foreach ($fieldSuggestions as $fieldSuggestion) {
            $column = $fieldSuggestion->column();
            $columnClass = $this->columnClassForFieldClass($fieldSuggestion->fieldClass());
            $label = $this->labelForColumn($column);
            $suffix = '';

            if (in_array($fieldSuggestion->fieldClass(), ['TextField', 'EmailField', 'PhoneField', 'UrlField', 'SlugField'], true)) {
                $suffix .= '->searchable()';
            }

            if (! in_array($fieldSuggestion->fieldClass(), ['TextareaField', 'PasswordField', 'FileField', 'ImageField'], true)) {
                $suffix .= '->sortable()';
            }

            $lines[] = sprintf("%s::make('%s')->label('%s')%s,", $columnClass, $column, $label, $suffix);
        }

        return $lines;
    }

    /**
     * @param  array<int, FilterSuggestion>  $filterSuggestions
     * @return array<int, string>
     */
    private function buildFilterLines(array $filterSuggestions): array
    {
        if ($filterSuggestions === []) {
            return [];
        }

        $lines = [];
        foreach ($filterSuggestions as $filterSuggestion) {
            $column = $filterSuggestion->column();
            $filterClass = $filterSuggestion->filterClass();
            $label = $this->labelForColumn($column);
            $lines[] = sprintf("%s::make('%s')->label('%s'),", $filterClass, $column, $label);
        }

        return $lines;
    }

    /**
     * @param  array<int, FieldSuggestion>  $fieldSuggestions
     * @return array<int, string>
     */
    private function buildFormFieldLines(array $fieldSuggestions): array
    {
        if ($fieldSuggestions === []) {
            return ["TextField::make('name')->label('Name'),"];
        }

        $lines = [];
        foreach ($fieldSuggestions as $fieldSuggestion) {
            $column = $fieldSuggestion->column();
            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            $fieldClass = $fieldSuggestion->fieldClass();
            $label = $this->labelForColumn($column);
            $lines[] = sprintf("%s::make('%s')->label('%s'),", $fieldClass, $column, $label);
        }

        return $lines === [] ? ["TextField::make('name')->label('Name'),"] : $lines;
    }

    /**
     * @param  array<int, FieldSuggestion>  $fieldSuggestions
     * @return array<int, string>
     */
    private function columnImports(array $fieldSuggestions): array
    {
        $imports = [];
        foreach ($fieldSuggestions as $fieldSuggestion) {
            $imports[] = 'SeviaFlow\\Core\\Resources\\Columns\\'.$this->columnClassForFieldClass($fieldSuggestion->fieldClass());
        }

        return $imports === [] ? ['SeviaFlow\\Core\\Resources\\Columns\\TextColumn'] : $imports;
    }

    /**
     * @param  array<int, FilterSuggestion>  $filterSuggestions
     * @return array<int, string>
     */
    private function filterImports(array $filterSuggestions): array
    {
        $imports = [];
        foreach ($filterSuggestions as $filterSuggestion) {
            $imports[] = 'SeviaFlow\\Core\\Resources\\Filters\\'.$filterSuggestion->filterClass();
        }

        return $imports;
    }

    /**
     * @param  array<int, FieldSuggestion>  $fieldSuggestions
     * @return array<int, string>
     */
    private function formImports(array $fieldSuggestions): array
    {
        $imports = [];
        foreach ($fieldSuggestions as $fieldSuggestion) {
            if (in_array($fieldSuggestion->column(), ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            $imports[] = 'SeviaFlow\\Core\\Resources\\Fields\\'.$fieldSuggestion->fieldClass();
        }

        return $imports === [] ? ['SeviaFlow\\Core\\Resources\\Fields\\TextField'] : $imports;
    }

    private function columnClassForFieldClass(string $fieldClass): string
    {
        return match ($fieldClass) {
            'ToggleField' => 'BooleanColumn',
            'NumberField', 'MoneyField' => 'NumberColumn',
            default => 'TextColumn',
        };
    }

    private function labelForColumn(string $column): string
    {
        if (strtolower($column) === 'id') {
            return 'ID';
        }

        return Str::headline(str_replace('_', ' ', $column));
    }

    /**
     * @param  array<int, RelationSuggestion>  $relationSuggestions
     */
    private function renderResourceImports(
        string $resourceNamespace,
        string $modelClass,
        string $tableClass,
        string $formClass,
        array $relationSuggestions
    ): string {
        $imports = [
            $modelClass,
            $resourceNamespace.'\\Forms\\'.$formClass,
            $resourceNamespace.'\\Tables\\'.$tableClass,
            'SeviaFlow\\Core\\Resources\\Form',
            'SeviaFlow\\Core\\Resources\\Resource',
            'SeviaFlow\\Core\\Resources\\Table',
        ];

        foreach ($relationSuggestions as $relation) {
            if (! $relation->isHighConfidence()) {
                continue;
            }

            $relationImport = match ($relation->relationType()) {
                'belongsToMany' => 'SeviaFlow\\Core\\Resources\\Relations\\BelongsToManyRelation',
                'hasMany' => 'SeviaFlow\\Core\\Resources\\Relations\\HasManyRelation',
                'hasOne' => 'SeviaFlow\\Core\\Resources\\Relations\\HasOneRelation',
                'morphMany' => 'SeviaFlow\\Core\\Resources\\Relations\\MorphManyRelation',
                'morphOne' => 'SeviaFlow\\Core\\Resources\\Relations\\MorphOneRelation',
                'morphToMany' => 'SeviaFlow\\Core\\Resources\\Relations\\MorphToManyRelation',
                default => null,
            };

            if ($relationImport === null) {
                continue;
            }

            $imports[] = $relationImport;

            $relatedResourceShort = $relation->relatedModelShortName();
            if ($relatedResourceShort === null || $relatedResourceShort === '') {
                continue;
            }

            $imports[] = sprintf(
                '%s\\%sResource\\%sResource',
                $this->resourcesRootNamespace($resourceNamespace),
                $relatedResourceShort,
                $relatedResourceShort
            );
        }

        $imports = array_values(array_unique($imports));
        $this->sortImports($imports);

        return implode(
            "\n",
            array_map(static fn (string $import): string => 'use '.$import.';', $imports)
        );
    }

    /**
     * @param  array<int, RelationSuggestion>  $relationSuggestions
     */
    private function renderRelationLines(array $relationSuggestions): string
    {
        $lines = [];

        foreach ($relationSuggestions as $relation) {
            if (! $relation->isHighConfidence()) {
                continue;
            }

            $relatedResourceShort = $relation->relatedModelShortName();
            if ($relatedResourceShort === null || $relatedResourceShort === '') {
                continue;
            }

            $resourceClass = $relatedResourceShort.'Resource::class';
            $name = $relation->name();

            $line = match ($relation->relationType()) {
                'hasOne' => "HasOneRelation::make('{$name}')->resource({$resourceClass}),",
                'hasMany' => "HasManyRelation::make('{$name}')->resource({$resourceClass}),",
                'belongsToMany' => "BelongsToManyRelation::make('{$name}')->resource({$resourceClass}),",
                'morphOne' => "MorphOneRelation::make('{$name}')->resource({$resourceClass}),",
                'morphMany' => "MorphManyRelation::make('{$name}')->resource({$resourceClass}),",
                'morphToMany' => "MorphToManyRelation::make('{$name}')->resource({$resourceClass}),",
                default => null,
            };

            if ($line !== null) {
                $lines[] = $line;
            }
        }

        if ($lines === []) {
            return '//';
        }

        return implode("\n            ", $lines);
    }

    private function resourcesRootNamespace(string $resourceNamespace): string
    {
        if (! str_ends_with($resourceNamespace, 'Resource')) {
            return $resourceNamespace;
        }

        $root = preg_replace('/\\\\[^\\\\]+Resource$/', '', $resourceNamespace);

        return is_string($root) && $root !== '' ? $root : $resourceNamespace;
    }

    /**
     * @param  array<int, string>  $imports
     */
    private function sortImports(array &$imports): void
    {
        sort($imports, SORT_STRING | SORT_FLAG_CASE);
    }
}
