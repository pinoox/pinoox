<?php



use Pinoox\Component\Database\AppDatabaseResolver;

use Pinoox\Component\Database\DatabaseConfig;

use Pinoox\Component\Database\DatabaseManager;

use Pinoox\Component\Kernel\Loader;

use Pinoox\Component\Package\AppEnv\AppEnvBridge;

use Pinoox\Component\Package\AppEnv\AppEnvLoader;

use Pinoox\Portal\App\AppEngine;

use Pinoox\Portal\App\AppProvider;

use Pinoox\Support\SystemConfig;



beforeEach(function () {

    Loader::setBasePath(testProjectRoot());

    AppProvider::___();

    AppEnvLoader::reset();

    AppEnvBridge::reset();

    deleteTestApp('com_test_env_database');

    deleteTestApp('com_test_env_database_app');

    deleteTestApp('com_test_env_database_pinker');

    deleteTestApp('com_test_env_database_lang');

    deleteTestApp('com_test_env_database_use');

    deleteTestApp('com_test_env_database_prefix');

    deleteTestApp('com_test_env_database_env');

    AppEngine::__rebuild();

});



afterEach(function () {

    deleteTestApp('com_test_env_database');

    deleteTestApp('com_test_env_database_app');

    deleteTestApp('com_test_env_database_pinker');

    deleteTestApp('com_test_env_database_lang');

    deleteTestApp('com_test_env_database_use');

    deleteTestApp('com_test_env_database_prefix');

    deleteTestApp('com_test_env_database_env');

    AppEngine::__rebuild();



    foreach (['DB_CONNECTION', 'DB_HOST', 'DB_DATABASE'] as $key) {

        putenv($key);

        unset($_ENV[$key], $_SERVER[$key]);

    }



    SystemConfig::clearCache();

});



function platformDatabaseFixture(): array

{

    return [

        'default' => 'mysql',

        'connections' => [

            'mysql' => [

                'driver' => 'sqlite',

                'database' => ':memory:',

                'prefix' => 'pinx_',

            ],

            'mariadb' => [

                'driver' => 'sqlite',

                'database' => ':memory:',

                'prefix' => 'mar_',

            ],

        ],

    ];

}



it('uses platform core connection when app database config is empty', function () {

    $manager = new DatabaseManager(new Illuminate\Container\Container());

    $manager->registerCoreConnection([

        'driver' => 'sqlite',

        'database' => ':memory:',

        'prefix' => '',

    ]);



    writeTestApp('com_test_env_database', [

        'database' => null,

    ]);

    AppEngine::__rebuild();



    expect($manager->connectionNameForPackage('com_test_env_database'))->toBe(DatabaseManager::DEFAULT_CONNECTION);

});



it('registers a dedicated app connection from app.php database block', function () {

    $package = 'com_test_env_database_app';

    $manager = new DatabaseManager(new Illuminate\Container\Container());

    $manager->registerCoreConnection([

        'driver' => 'sqlite',

        'database' => ':memory:',

        'prefix' => '',

    ]);



    writeTestApp($package, [

        'database' => [

            'driver' => 'sqlite',

            'database' => ':memory:',

            'prefix' => 'shop_',

        ],

    ]);

    AppEngine::__rebuild();



    expect(AppEngine::exists($package))->toBeTrue()

        ->and(AppEngine::config($package)->get('database'))->toBeArray()

        ->and($manager->connectionNameForPackage($package))->toBe('app_' . $package . '_default')

        ->and($manager->app($package)->getTablePrefix())->toBe('shop_');

});



it('reuses a named platform connection when database.use is set', function () {

    $package = 'com_test_env_database_use';

    $platform = platformDatabaseFixture();



    $resolved = AppDatabaseResolver::resolve(

        ['use' => 'mariadb'],

        null,

        $platform,

    );



    expect($resolved)->toHaveKey('default')

        ->and($resolved['default']['driver'])->toBe('sqlite')

        ->and($resolved['default']['prefix'])->toBe('mar_');



    $manager = new DatabaseManager(new Illuminate\Container\Container());
    $manager->registerCoreConnection($platform['connections']['mysql']);

    writeTestApp($package, [
        'database' => [
            'use' => 'mariadb',
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => 'mar_',
        ],
    ]);
    AppEngine::__rebuild();

    expect($manager->connectionNameForPackage($package))->toBe('app_' . $package . '_default')
        ->and($manager->app($package)->getTablePrefix())->toBe('mar_');
});



it('applies prefix override on a platform connection', function () {

    $platform = platformDatabaseFixture();



    $resolved = AppDatabaseResolver::resolve(

        ['use' => 'mysql', 'prefix' => 'welcome_'],

        null,

        $platform,

    );



    expect($resolved['default']['prefix'])->toBe('welcome_')

        ->and($resolved['default']['driver'])->toBe('sqlite');

});



it('shares platform default connection when use is platform without overrides', function () {

    $platform = platformDatabaseFixture();



    expect(AppDatabaseResolver::resolve(['use' => 'platform'], null, $platform))->toBe([])

        ->and(AppDatabaseResolver::resolve(['connection' => 'default'], null, $platform))->toBe([]);

});



it('registers prefix-only overrides from table.prefix or database.prefix', function () {

    $platform = platformDatabaseFixture();

    $manager = new DatabaseManager(new Illuminate\Container\Container());

    $manager->registerCoreConnection($platform['connections']['mysql']);



    writeTestApp('com_test_env_database_prefix', [

        'database' => null,

        'table' => ['prefix' => 'welcome_'],

    ]);

    AppEngine::__rebuild();



    expect($manager->connectionNameForPackage('com_test_env_database_prefix'))->toBe('app_com_test_env_database_prefix_default')

        ->and($manager->app('com_test_env_database_prefix')->getTablePrefix())->toBe('welcome_');



    $resolved = AppDatabaseResolver::resolve(['prefix' => 'shop_'], null, $platform);

    expect($resolved['default']['prefix'])->toBe('shop_');

});



it('maps app .env database keys through AppEnvBridge', function () {

    $package = 'com_test_env_database_env';



    writeTestApp($package, [

        'database' => null,

        'lang' => 'en',

    ]);



    $appRoot = testProjectRoot() . '/apps/' . $package;

    file_put_contents($appRoot . '/.env', "DB_USE=mariadb\nDB_PREFIX=envshop_\nLANG=fa\n");



    AppEngine::__rebuild();



    $config = AppEngine::config($package);

    $database = $config->get('database');



    expect($config->get('lang'))->toBe('fa')

        ->and($database)->toBeArray()

        ->and($database['use'] ?? null)->toBe('mariadb')

        ->and($database['prefix'] ?? null)->toBe('envshop_')

        ->and(AppEnvBridge::effective($package))->toHaveKeys(['DB_USE', 'DB_PREFIX', 'LANG']);



    $resolved = AppDatabaseResolver::resolve($database, null, platformDatabaseFixture());

    expect($resolved['default']['prefix'])->toBe('envshop_');

});



it('does not map project DB_CONNECTION from app .env onto app config', function () {

    $package = 'com_test_env_database_lang';



    writeTestApp($package, [

        'database' => null,

        'lang' => 'en',

    ]);



    $appRoot = testProjectRoot() . '/apps/' . $package;

    file_put_contents($appRoot . '/.env', "DB_CONNECTION=mysql\nLANG=fa\n");



    AppEngine::__rebuild();



    $config = AppEngine::config($package);



    expect($config->get('lang'))->toBe('fa')

        ->and($config->get('database'))->toBeNull()

        ->and(AppEnvBridge::effective($package))->not->toHaveKey('DB_CONNECTION');

});



it('lets project root DB_CONNECTION env override pinker default connection name', function () {

    putenv('DB_CONNECTION=mysql');

    $_ENV['DB_CONNECTION'] = 'mysql';

    $_SERVER['DB_CONNECTION'] = 'mysql';

    SystemConfig::clearCache();



    expect(DatabaseConfig::requestedConnectionName())->toBe('mysql');

});



it('loads app database overrides from pinker state when present', function () {

    $package = 'com_test_env_database_pinker';



    writeTestApp($package, [

        'database' => null,

    ]);



    $stateDir = testProjectRoot() . '/pinker/state/apps/' . $package;

    $stateFile = $stateDir . '/app.php';



    if (!is_dir($stateDir)) {

        mkdir($stateDir, 0777, true);

    }



    $stateBackup = is_file($stateFile) ? file_get_contents($stateFile) : null;



    file_put_contents($stateFile, <<<'PHP'

<?php

return [

    '__pinker_override__' => true,

    'schema' => 1,

    'data' => [

        'database.driver' => 'sqlite',

        'database.database' => ':memory:',

        'database.prefix' => 'envshop_',

    ],

    'remove' => [],

    'info' => [],

];

PHP);



    try {

        AppEngine::__rebuild();



        $config = AppEngine::config($package);

        $database = $config->get('database');



        expect($database)->toBeArray()

            ->and($database['driver'] ?? null)->toBe('sqlite')

            ->and($database['prefix'] ?? null)->toBe('envshop_');



        $manager = new DatabaseManager(new Illuminate\Container\Container());

        $manager->registerCoreConnection([

            'driver' => 'sqlite',

            'database' => ':memory:',

            'prefix' => '',

        ]);



        expect($manager->connectionNameForPackage($package))->toBe('app_' . $package . '_default')

            ->and($manager->app($package)->getTablePrefix())->toBe('envshop_');

    } finally {

        if ($stateBackup !== null) {

            file_put_contents($stateFile, $stateBackup);

        } elseif (is_file($stateFile)) {

            unlink($stateFile);

        }

    }

});



