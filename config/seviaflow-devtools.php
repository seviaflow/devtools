<?php

declare(strict_types=1);

use Illuminate\Support\Env;

return [
    'enabled' => Env::get('SEVIAFLOW_DEVTOOLS_ENABLED', true),
    'discovery' => [
        'enabled' => true,
        'model_paths' => [
            app_path('Models'),
        ],
        'model_namespaces' => [
            'App\\Models',
        ],
        'ignored_models' => [],
    ],
    'generation' => [
        'resources_path' => app_path('SeviaFlow/Resources'),
        'resources_namespace' => 'App\\SeviaFlow\\Resources',
        'stubs_path' => base_path('stubs/seviaflow'),
    ],
];
