<?php

use Pinoox\Component\Database\DatabaseManager;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Model\Table;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Database\DB;

beforeEach(function () {
    Loader::setBasePath(testProjectRoot());
    AppProvider::___();
    deleteTestApp('com_test_database');
    deleteTestApp('com_test_named_database');
    deleteTestApp('com_test_relation_core');
    deleteTestApp('com_test_relation_manager');
    deleteTestApp('com_test_shared_tables');
    deleteTestApp('com_test_dedicated_tables');
    deleteTestApp('com_test_prefixed_tables');
    deleteTestApp('com_test_derived_prefix');
    deleteTestApp('com_test_default');
    AppEngine::__rebuild();

    if (!class_exists('App\com_test_database\Model\ExampleModel')) {
        eval('namespace App\com_test_database\Model; class ExampleModel extends \Pinoox\Component\Database\Model {}');
    }
    if (!class_exists('App\com_test_relation_core\Model\CoreUserModel')) {
        eval('namespace App\com_test_relation_core\Model; class CoreUserModel extends \Pinoox\Component\Database\Model { protected $table = "users"; protected $primaryKey = "user_id"; public $timestamps = false; protected $guarded = []; public function notifications() { return $this->hasMany(\App\com_test_relation_manager\Model\NotificationModel::class, "user_id", "user_id"); } }');
    }
    if (!class_exists('App\com_test_relation_manager\Model\NotificationModel')) {
        eval('namespace App\com_test_relation_manager\Model; class NotificationModel extends \Pinoox\Component\Database\Model { protected $table = "notifications"; protected $primaryKey = "notification_id"; public $timestamps = false; protected $guarded = []; public function user() { return $this->belongsTo(\App\com_test_relation_core\Model\CoreUserModel::class, "user_id", "user_id"); } }');
    }
});

afterEach(function () {
    deleteTestApp('com_test_database');
    deleteTestApp('com_test_named_database');
    deleteTestApp('com_test_relation_core');
    deleteTestApp('com_test_relation_manager');
    deleteTestApp('com_test_shared_tables');
    deleteTestApp('com_test_dedicated_tables');
    deleteTestApp('com_test_prefixed_tables');
    deleteTestApp('com_test_derived_prefix');
    deleteTestApp('com_test_default');
    AppEngine::__rebuild();
});

it('resolves core, fallback, and app-specific database connections', function () {
    $manager = new DatabaseManager(new Illuminate\Container\Container());
    $manager->registerCoreConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    writeTestApp('com_test_database', [
        'database' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
    ]);
    writeTestApp('com_test_default', [
        'database' => null,
    ]);
    AppEngine::__rebuild();

    expect($manager->core()->getName())->toBe(DatabaseManager::CORE_CONNECTION)
        ->and($manager->connectionNameForPackage('com_test_default'))->toBe(DatabaseManager::DEFAULT_CONNECTION)
        ->and($manager->connectionNameForPackage('com_test_database'))->toBe('app_com_test_database_default')
        ->and($manager->connectionNameForModel(Pinoox\Model\UserModel::class))->toBe(DatabaseManager::CORE_CONNECTION)
        ->and($manager->connectionNameForModel(App\com_test_database\Model\ExampleModel::class))->toBe('app_com_test_database_default');
});

it('supports named app database connections', function () {
    $manager = new DatabaseManager(new Illuminate\Container\Container());
    $manager->registerCoreConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    writeTestApp('com_test_named_database', [
        'database' => [
            'default' => 'primary',
            'connections' => [
                'primary' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                'analytics' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
            ],
        ],
    ]);
    AppEngine::__rebuild();

    expect($manager->connectionNameForPackage('com_test_named_database'))->toBe('app_com_test_named_database_primary')
        ->and($manager->connectionNameForPackage('com_test_named_database', 'analytics'))->toBe('app_com_test_named_database_analytics')
        ->and($manager->app('com_test_named_database')->getName())->toBe('app_com_test_named_database_primary')
        ->and($manager->app('com_test_named_database', 'analytics')->getName())->toBe('app_com_test_named_database_analytics');
});

it('uses the core connection prefix without duplicating it in resolved table names', function () {
    $manager = new DatabaseManager(new Illuminate\Container\Container());
    $manager->registerCoreConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => 'pinx_',
    ]);

    expect($manager->core()->getTablePrefix())->toBe('pinx_')
        ->and($manager->tableName('user', 'platform'))->toBe('user')
        ->and($manager->tableName('user as u', 'platform'))->toBe('user AS u');
});

it('derives an app connection from core when only the table prefix is customized', function () {
    $manager = new DatabaseManager(new Illuminate\Container\Container());
    $manager->registerCoreConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => 'pinx_',
    ]);

    writeTestApp('com_test_derived_prefix', [
        'database' => null,
        'table' => [
            'prefix' => 'welcome_',
        ],
    ]);
    AppEngine::__rebuild();

    $connectionName = $manager->connectionNameForPackage('com_test_derived_prefix');

    expect($connectionName)->toBe('app_com_test_derived_prefix_default')
        ->and($manager->app('com_test_derived_prefix')->getTablePrefix())->toBe('welcome_')
        ->and($manager->tableName('category', 'com_test_derived_prefix'))->toBe('category')
        ->and($manager->physicalTableName('category', 'com_test_derived_prefix'))->toBe('welcome_category')
        ->and($manager->tableName('user', 'platform'))->toBe('user')
        ->and($manager->physicalTableName(Table::FILE, 'platform'))->toBe('pinx_file');
});

it('loads relations between models that belong to different app databases', function () {
    writeTestApp('com_test_relation_core', [
        'database' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
    ]);
    writeTestApp('com_test_relation_manager', [
        'database' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
    ]);
    AppEngine::__rebuild();

    DB::registerCoreConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    DB::setAsGlobal();
    DB::bootEloquent();

    $coreConnection = DB::app('com_test_relation_core');
    $managerConnection = DB::app('com_test_relation_manager');

    $coreConnection->getSchemaBuilder()->create('users', function ($table) {
        $table->integer('user_id')->primary();
        $table->string('username');
    });
    $managerConnection->getSchemaBuilder()->create('notifications', function ($table) {
        $table->integer('notification_id')->primary();
        $table->integer('user_id');
        $table->string('title');
    });

    $coreConnection->table('users')->insert([
        'user_id' => 10,
        'username' => 'pinoox-user',
    ]);
    $managerConnection->table('notifications')->insert([
        'notification_id' => 99,
        'user_id' => 10,
        'title' => 'Manager notification',
    ]);

    $notification = App\com_test_relation_manager\Model\NotificationModel::with('user')->first();
    $user = App\com_test_relation_core\Model\CoreUserModel::with('notifications')->first();

    expect($notification->getConnectionName())->toBe('app_com_test_relation_manager_default')
        ->and($notification->user->getConnectionName())->toBe('app_com_test_relation_core_default')
        ->and($notification->user->username)->toBe('pinoox-user')
        ->and($user->notifications)->toHaveCount(1)
        ->and($user->notifications->first()->title)->toBe('Manager notification');
});

it('resolves table names using core, shared app, dedicated app, and explicit prefixes', function () {
    $manager = new DatabaseManager(new Illuminate\Container\Container());
    $manager->registerCoreConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    writeTestApp('com_test_shared_tables', [
        'database' => null,
    ]);
    writeTestApp('com_test_dedicated_tables', [
        'database' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
    ]);
    writeTestApp('com_test_prefixed_tables', [
        'database' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'table_prefix' => 'mgr_',
        ],
    ]);
    AppEngine::__rebuild();

    expect($manager->tableName('user', 'platform'))->toBe('pinx_user')
        ->and(Table::USER)->toBe('user')
        ->and(Table::user('u'))->toBe('pinx_user AS u')
        ->and($manager->tableName(Table::USER, 'platform'))->toBe('pinx_user')
        ->and($manager->tableName('pinx_user', 'com_test_shared_tables'))->toBe('pinx_user')
        ->and($manager->tableName('notifications', 'com_test_shared_tables'))->toBe('tables_notifications')
        ->and($manager->tableName('notifications', 'com_test_dedicated_tables'))->toBe('notifications')
        ->and($manager->tableName('notifications', 'com_test_prefixed_tables'))->toBe('mgr_notifications')
        ->and($manager->tableName('mgr_notifications', 'com_test_prefixed_tables'))->toBe('mgr_notifications')
        ->and($manager->tableName('notifications as n', 'com_test_prefixed_tables'))->toBe('mgr_notifications AS n');
});

it('keeps migration table access pointed to the central history table', function () {
    $manager = new DatabaseManager(new Illuminate\Container\Container());
    $manager->registerCoreConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => 'pinx_',
    ]);

    expect(Table::HISTORY)->toBe('history')
        ->and(Table::MIGRATION)->toBe('history')
        ->and($manager->physicalTableName(Table::MIGRATION, 'platform'))->toBe('pinx_history')
        ->and(class_exists(Pinoox\Model\HistoryModel::class))->toBeTrue();
});
