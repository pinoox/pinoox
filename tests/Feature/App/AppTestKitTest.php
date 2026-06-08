<?php

use Pinoox\Component\Test\AppTestKit;
use Pinoox\Component\Test\TestResponse;
use Pinoox\Portal\App\AppEngine;

afterEach(function () {
    deleteFakeApp('com_test_appkit');
});

it('boots pinoox in test mode', function () {
    AppTestKit::boot();

    expect(config('~pinoox')->get('mode'))->toBe('test');
});

it('creates and removes fake apps', function () {
    fakeApp('com_test_appkit', [
        'routes/web.php' => "<?php\n\nuse function Pinoox\\Router\\get;\n\nget('/', fn () => 'ok');\n",
    ]);

    expect(AppEngine::exists('com_test_appkit'))->toBeTrue()
        ->and(appPath('com_test_appkit'))->toBeDirectory();
});

it('runs callbacks inside app context', function () {
    fakeApp('com_test_appkit');

    $seen = inApp('com_test_appkit', fn () => \Pinoox\Portal\App\App::package());

    expect($seen)->toBe('com_test_appkit');
});

it('builds test responses with helpers', function () {
    $response = new TestResponse(new \Pinoox\Component\Http\Response('{"ok":true}', 200));

    $response->assertOk()->assertJsonPath('ok', true);
});

it('detects package from app test path', function () {
    $file = str_replace('\\', '/', AppTestKit::projectRoot()) . '/apps/com_demo/tests/Feature/DemoTest.php';

    expect(AppTestKit::detectPackageFromPath($file))->toBe('com_demo');
});

it('exposes global app test helpers', function () {
    expect(function_exists('appGet'))->toBeTrue()
        ->and(function_exists('inApp'))->toBeTrue()
        ->and(function_exists('fakeApp'))->toBeTrue();
});

