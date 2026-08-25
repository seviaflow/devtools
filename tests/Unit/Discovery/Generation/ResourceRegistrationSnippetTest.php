<?php

declare(strict_types=1);

use SeviaFlow\DevTools\Discovery\Generation\ResourceRegistrationSnippet;

it('builds resource registration entry from resource metadata', function (): void {
    $entry = (new ResourceRegistrationSnippet)->makeEntry(
        resourceBase: 'User',
        resourceNamespace: 'App\\SeviaFlow\\Resources\\UserResource',
        resourceClass: 'UserResource'
    );

    expect($entry)->toBe("'users' => App\\SeviaFlow\\Resources\\UserResource\\UserResource::class,");
});
