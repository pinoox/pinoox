<?php

use Pinoox\PinDoc\Api\ApiDocsGenerator;
use Pinoox\PinDoc\Api\ApiRouteLoader;
use Pinoox\PinDoc\Api\AppApiRegistry;
use Pinoox\Component\Package\App as PackageApp;
use Pinoox\Component\Router\RouteName;
use Pinoox\Component\Router\Router as RouterComponent;
use Pinoox\PinDoc\GraphQL\GraphQLRegistry;
use Pinoox\Portal\App\AppEngine;

beforeEach(function () {
    apiSystemDeleteTestApp('com_test_api_routes');
    apiSystemDeleteTestApp('com_test_api_old_routes');
    AppEngine::__rebuild();
});

afterEach(function () {
    apiSystemDeleteTestApp('com_test_api_routes');
    apiSystemDeleteTestApp('com_test_api_old_routes');
    AppEngine::__rebuild();
});

it('normalizes app api route definitions', function () {
    $registry = new AppApiRegistry();

    $entry = $registry->normalize('com_blog', 'Blog Team', [
        'version' => 'v1',
        'prefix' => 'blog',
        'flow' => ['auth:api'],
        'routes' => [
            [
                'method' => 'GET',
                'uri' => '/posts',
                'action' => ['PostController', 'index'],
                'name' => 'posts.index',
                'permission' => 'blog.posts.view',
                'description' => 'Get list of blog posts',
            ],
        ],
    ]);

    expect($entry['routes'][0]['full_uri'])->toBe('/api/v1/blog/posts')
        ->and($entry['routes'][0]['flow'])->toBe(['auth:api'])
        ->and($entry['routes'][0]['permission'])->toBe('blog.posts.view');
});

it('loads api routes into the current router', function () {
    $app = $this->createMock(PackageApp::class);
    $app->method('package')->willReturn('com_blog');

    $router = new RouterComponent(new RouteName(), $app);
    $loader = new ApiRouteLoader(new class extends AppApiRegistry {
        public function all(?string $app = null, ?string $version = null): array
        {
            return [
                'com_blog' => [
                    'app' => 'com_blog',
                    'owner' => 'Blog Team',
                    'version' => 'v1',
                    'prefix' => 'blog',
                    'flow' => [],
                    'routes' => [
                        [
                            'app' => 'com_blog',
                            'owner' => 'Blog Team',
                            'version' => 'v1',
                            'method' => 'GET',
                            'uri' => '/posts',
                            'full_uri' => '/api/v1/blog/posts',
                            'action' => fn() => 'ok',
                            'name' => 'posts.index',
                            'flow' => [],
                            'permission' => 'blog.posts.view',
                            'auth' => null,
                            'rate_limit' => null,
                            'request' => null,
                            'resource' => null,
                            'description' => 'Get posts',
                            'params' => [],
                            'body' => [],
                            'response' => [],
                        ],
                    ],
                ],
            ];
        }
    });

    $loader->load($router);

    $paths = $router->getAllPath();

    expect($paths)->toHaveKey('api.v1.com_blog.posts.index')
        ->and($paths['api.v1.com_blog.posts.index'])->toBe('/api/v1/blog/posts');
});

it('registers routes with the fluent route builder shortcut', function () {
    $app = $this->createMock(PackageApp::class);
    $app->method('package')->willReturn('com_blog');

    $router = new RouterComponent(new RouteName(), $app);

    $router->route('/posts', fn() => 'ok')
        ->get()
        ->name('posts.index')
        ->flow('auth:api')
        ->flow('signed')
        ->flow(['auth:api', 'verified'])
        ->tags(['blog'])
        ->register();

    $paths = $router->getAllPath();
    $route = $router->all()['posts.index'];
    $pinooxRoute = $route->getDefault('_router');

    expect($paths)->toHaveKey('posts.index')
        ->and($paths['posts.index'])->toBe('/posts')
        ->and($route->getMethods())->toContain('GET')
        ->and($pinooxRoute->flows)->toBe(['auth:api', 'signed', 'verified'])
        ->and($route->getOption('compiler_class'))->toBe(Symfony\Component\Routing\RouteCompiler::class);
});

it('generates rest api markdown documentation for external audience by default', function () {
    $registry = new class extends AppApiRegistry {
        public function all(?string $app = null, ?string $version = null): array
        {
            return [
                'com_blog' => [
                    'app' => 'com_blog',
                    'owner' => 'Blog Team',
                    'app_meta' => [
                        'package' => 'com_blog',
                        'name' => 'blog',
                        'title' => 'Blog',
                        'description' => 'Blog application API',
                        'developer' => 'Blog Team',
                        'version_name' => '1.0',
                        'version_code' => '1',
                        'lang' => 'en',
                        'theme' => 'default',
                        'icon' => '',
                        'icon_url' => '',
                    ],
                    'version' => 'v1',
                    'prefix' => 'blog',
                    'flow' => [],
                    'docs' => ['title' => 'Blog API'],
                    'routes' => [
                        [
                            'method' => 'GET',
                            'full_uri' => '/api/v1/blog/posts',
                            'name' => 'posts.index',
                            'action' => ['PostController', 'index'],
                            'flow' => ['auth:api'],
                            'permission' => 'blog.posts.view',
                            'auth' => null,
                            'rate_limit' => null,
                            'description' => 'Get list of blog posts',
                            'response' => [],
                        ],
                    ],
                ],
            ];
        }
    };

    $generator = new ApiDocsGenerator($registry);

    $docs = $generator->generate('md');

    expect($docs)->toContain('# Blog API')
        ->and($docs)->toContain('/api/v1/blog/posts')
        ->and($docs)->toContain('Public API docs')
        ->and($docs)->toContain('Authentication required')
        ->and($docs)->not->toContain('auth:api')
        ->and($docs)->not->toContain('blog.posts.view')
        ->and($docs)->not->toContain('PostController');
});

it('generates rest api markdown documentation for internal audience', function () {
    $generator = new ApiDocsGenerator(new class extends AppApiRegistry {
        public function all(?string $app = null, ?string $version = null): array
        {
            return [
                'com_blog' => [
                    'app' => 'com_blog',
                    'owner' => 'Blog Team',
                    'app_meta' => [
                        'package' => 'com_blog',
                        'name' => 'blog',
                        'title' => 'Blog',
                        'description' => 'Blog application API',
                        'developer' => 'Blog Team',
                        'version_name' => '1.0',
                        'version_code' => '1',
                        'lang' => 'en',
                        'theme' => 'default',
                        'icon' => '',
                        'icon_url' => '',
                    ],
                    'version' => 'v1',
                    'prefix' => 'blog',
                    'flow' => [],
                    'docs' => ['title' => 'Blog API'],
                    'routes' => [
                        [
                            'method' => 'GET',
                            'full_uri' => '/api/v1/blog/posts',
                            'name' => 'posts.index',
                            'action' => ['PostController', 'index'],
                            'flow' => ['auth:api'],
                            'permission' => 'blog.posts.view',
                            'auth' => null,
                            'rate_limit' => null,
                            'description' => 'Get list of blog posts',
                            'response' => [],
                        ],
                    ],
                ],
            ];
        }
    });

    $docs = $generator->generate('md', null, null, 'internal');

    expect($docs)->toContain('Developer docs')
        ->and($docs)->toContain('auth:api')
        ->and($docs)->toContain('blog.posts.view')
        ->and($docs)->toContain('PostController');
});

it('normalizes graphql app definitions', function () {
    $registry = new class extends GraphQLRegistry {
        public function normalizeForTest(array $items): array
        {
            $method = new ReflectionMethod(GraphQLRegistry::class, 'normalizeMap');
            $method->setAccessible(true);

            return $method->invoke($this, $items);
        }
    };

    $queries = $registry->normalizeForTest([
        'posts' => [
            'class' => 'PostsQuery',
            'permission' => 'blog.posts.view',
            'flow' => ['auth:api'],
            'description' => 'List posts',
        ],
    ]);

    expect($queries['posts']['class'])->toBe('PostsQuery')
        ->and($queries['posts']['permission'])->toBe('blog.posts.view')
        ->and($queries['posts']['flow'])->toBe(['auth:api']);
});

it('does not read middleware as a flow alias in api or graphql definitions', function () {
    $api = (new AppApiRegistry())->normalize('com_blog', 'Blog Team', [
        'version' => 'v1',
        'prefix' => 'blog',
        'middleware' => ['auth:api'],
        'routes' => [
            [
                'method' => 'GET',
                'uri' => '/posts',
                'middleware' => ['rate:posts'],
            ],
        ],
    ]);

    $graphql = new class extends GraphQLRegistry {
        public function normalizeForTest(array $items): array
        {
            $method = new ReflectionMethod(GraphQLRegistry::class, 'normalizeMap');
            $method->setAccessible(true);

            return $method->invoke($this, $items);
        }
    };

    $queries = $graphql->normalizeForTest([
        'posts' => [
            'class' => 'PostsQuery',
            'middleware' => ['auth:api'],
        ],
    ]);

    expect($api['flow'])->toBe([])
        ->and($api['routes'][0]['flow'])->toBe([])
        ->and($queries['posts']['flow'])->toBe([]);
});

it('discovers app api and graphql definitions from the central routes folder', function () {
    apiSystemWriteTestApp('com_test_api_routes', [
        'routes/api.php' => <<<'PHP'
<?php

return [
    'version' => 'v1',
    'prefix' => 'catalog',
    'flow' => ['auth:api'],
    'routes' => [
        [
            'method' => 'GET',
            'uri' => '/items',
            'action' => ['CatalogController', 'index'],
            'name' => 'items.index',
        ],
    ],
];
PHP,
        'routes/graphql.php' => <<<'PHP'
<?php

return [
    'queries' => [
        'items' => [
            'class' => 'ItemsQuery',
            'flow' => ['auth:api'],
        ],
    ],
];
PHP,
    ]);
    AppEngine::__rebuild();

    $api = (new AppApiRegistry())->all('com_test_api_routes');
    $graphql = (new GraphQLRegistry())->all('com_test_api_routes');

    expect($api['com_test_api_routes']['routes'][0]['full_uri'])->toBe('/api/v1/catalog/items')
        ->and($api['com_test_api_routes']['routes'][0]['flow'])->toBe(['auth:api'])
        ->and($graphql['com_test_api_routes']['queries']['items']['flow'])->toBe(['auth:api']);
});

it('lets the main api route file manually compose nested route files', function () {
    apiSystemWriteTestApp('com_test_api_routes', [
        'routes/api.php' => <<<'PHP'
<?php

return [
    'version' => 'v1',
    'prefix' => 'catalog',
    'flow' => ['auth:api'],
    'routes' => array_merge(
        require __DIR__ . '/api/categories.php',
        require __DIR__ . '/api/posts.php',
    ),
];
PHP,
        'routes/api/posts.php' => <<<'PHP'
<?php

return [
    [
        'method' => 'GET',
        'uri' => '/posts',
        'action' => ['PostController', 'index'],
        'name' => 'posts.index',
    ],
];
PHP,
        'routes/api/categories.php' => <<<'PHP'
<?php

return [
    [
        'method' => 'POST',
        'uri' => '/categories',
        'action' => ['CategoryController', 'store'],
        'name' => 'categories.store',
        'flow' => ['rate:write'],
    ],
];
PHP,
    ]);
    AppEngine::__rebuild();

    $routes = (new AppApiRegistry())->all('com_test_api_routes')['com_test_api_routes']['routes'];

    expect(array_column($routes, 'full_uri'))->toBe([
        '/api/v1/catalog/categories',
        '/api/v1/catalog/posts',
    ])
        ->and($routes[0]['flow'])->toBe(['auth:api', 'rate:write'])
        ->and($routes[1]['flow'])->toBe(['auth:api']);
});

it('loads separate api versions from routes/api.php and routes/api-v2.php', function () {
    apiSystemWriteTestApp('com_test_api_routes', [
        'routes/api.php' => <<<'PHP'
<?php

return [
    'version' => 'v1',
    'prefix' => 'catalog',
    'routes' => [
        [
            'method' => 'GET',
            'uri' => '/items',
            'action' => ['CatalogController', 'index'],
            'name' => 'items.index',
        ],
    ],
];
PHP,
        'routes/api-v2.php' => <<<'PHP'
<?php

return [
    'version' => 'v2',
    'prefix' => 'catalog',
    'routes' => [
        [
            'method' => 'GET',
            'uri' => '/items',
            'action' => ['CatalogController', 'indexV2'],
            'name' => 'items.index',
        ],
    ],
];
PHP,
    ]);
    AppEngine::__rebuild();

    $registry = new AppApiRegistry();
    $entries = $registry->all('com_test_api_routes');

    expect($entries)->toHaveKeys(['com_test_api_routes:v1', 'com_test_api_routes:v2'])
        ->and($entries['com_test_api_routes:v1']['routes'][0]['full_uri'])->toBe('/api/v1/catalog/items')
        ->and($entries['com_test_api_routes:v2']['routes'][0]['full_uri'])->toBe('/api/v2/catalog/items');

    $html = (new ApiDocsGenerator($registry))->generate('html', 'com_test_api_routes');

    expect($html)->toContain('id="api-version-select"')
        ->and($html)->toContain('<option value="v1">V1</option>')
        ->and($html)->toContain('<option value="v2">V2</option>')
        ->and($html)->toContain('v1_get_items_index')
        ->and($html)->toContain('v2_get_items_index');
});

it('does not auto-discover unlinked nested api and graphql route files', function () {
    apiSystemWriteTestApp('com_test_api_routes', [
        'routes/api/posts.php' => <<<'PHP'
<?php

return [
    [
        'method' => 'GET',
        'uri' => '/posts',
        'action' => ['PostController', 'index'],
        'name' => 'posts.index',
    ],
];
PHP,
        'routes/graphql/queries.php' => <<<'PHP'
<?php

return [
    'posts' => 'PostsQuery',
];
PHP,
    ]);
    AppEngine::__rebuild();

    expect((new AppApiRegistry())->all('com_test_api_routes'))->toBe([])
        ->and((new GraphQLRegistry())->all('com_test_api_routes'))->toBe([]);
});

it('ignores old api and graphql route locations', function () {
    apiSystemWriteTestApp('com_test_api_old_routes', [
        'api/routes.php' => <<<'PHP'
<?php

return [
    'version' => 'v1',
    'prefix' => 'legacy',
    'routes' => [
        [
            'method' => 'GET',
            'uri' => '/legacy',
            'action' => ['LegacyController', 'index'],
            'name' => 'legacy.index',
        ],
    ],
];
PHP,
        'graphql/graphql.php' => <<<'PHP'
<?php

return [
    'queries' => [
        'legacy' => 'LegacyQuery',
    ],
];
PHP,
    ]);
    AppEngine::__rebuild();

    expect((new AppApiRegistry())->all('com_test_api_old_routes'))->toBe([])
        ->and((new GraphQLRegistry())->all('com_test_api_old_routes'))->toBe([]);
});

