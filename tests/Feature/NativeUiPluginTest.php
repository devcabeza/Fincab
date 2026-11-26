<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Native\Mobile\UI\NativeUIServiceProvider;

test('native mobile UI plugin is available to the application', function () {
    expect(class_exists(NativeUIServiceProvider::class))->toBeTrue();
});

test('native mobile UI plugin is registered for device builds', function () {
    Artisan::call('native:plugin:list', ['--json' => true]);

    $plugins = json_decode(Artisan::output(), true);

    expect(collect($plugins['registered'])->pluck('name'))
        ->toContain('nativephp/mobile-ui');
});
