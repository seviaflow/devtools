<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery\Generation;

use Illuminate\Support\Str;

final class ResourceRegistrationSnippet
{
    public function makeEntry(string $resourceBase, string $resourceNamespace, string $resourceClass): string
    {
        return sprintf(
            "'%s' => %s::class,",
            Str::kebab(Str::pluralStudly($resourceBase)),
            $resourceNamespace.'\\'.$resourceClass
        );
    }
}
