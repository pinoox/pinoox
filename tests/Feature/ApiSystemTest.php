<?php

use Pinoox\Api\ApiDocsGenerator;
use Pinoox\Api\ApiRouteLoader;
use Pinoox\Api\AppApiRegistry;
use Pinoox\Component\Package\App as PackageApp;
use Pinoox\Component\Router\RouteName;
use Pinoox\Component\Router\Router as RouterComponent;
use Pinoox\GraphQL\GraphQLRegistry;

it('normalizes app api route definitions', function () {
    $registry = new AppApiRegistry();

    $entry = $registry->normalize('com_blog', 'Blog Team', [
        'version' => 'v1',
        'prefix' => 'blog',
        'middleware' => ['auth:api'],
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

    expect($entry['routes'][0]['full_uri'])->toBe('/api/v1/apps/com_blog/blog/posts')
        ->and($entry['routes'][0]['middleware'])->toBe(['auth:api'])
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
                    'middleware' => [],
                    'routes' => [
                        [
                            'app' => 'com_blog',
                            'owner' => 'Blog Team',
                            'version' => 'v1',
                            'method' => 'GET',
                            'uri' => '/posts',
                            'full_uri' => '/api/v1/apps/com_blog/blog/posts',
                            'action' => fn() => 'ok',
                            'name' => 'posts.index',
                            'middleware' => [],
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
        ->and($paths['api.v1.com_blog.posts.index'])->toBe('/api/v1/apps/com_blog/blog/posts');
});

it('registers routes with the fluent route builder shortcut', function () {
    $app = $this->createMock(PackageApp::class);
    $app->method('package')->willReturn('com_blog');

    $router = new RouterComponent(new RouteName(), $app);

    $router->route('/posts', fn() => 'ok')
        ->get()
        ->name('posts.index')
        ->middleware('auth:api')
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

it('generates rest api markdown documentation', function () {
    $generator = new ApiDocsGenerator(new class extends AppApiRegistry {
        public function all(?string $app = null, ?string $version = null): array
        {
            return [
                'com_blog' => [
                    'app' => 'com_blog',
                    'owner' => 'Blog Team',
                    'version' => 'v1',
                    'prefix' => 'blog',
                    'middleware' => [],
                    'routes' => [
                        [
                            'method' => 'GET',
                            'full_uri' => '/api/v1/apps/com_blog/blog/posts',
                            'name' => 'posts.index',
                            'action' => ['PostController', 'index'],
                            'middleware' => ['auth:api'],
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

    $docs = $generator->generate('md');

    expect($docs)->toContain('GET /api/v1/apps/com_blog/blog/posts')
        ->and($docs)->toContain('blog.posts.view');
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
            'middleware' => ['auth:api'],
            'description' => 'List posts',
        ],
    ]);

    expect($queries['posts']['class'])->toBe('PostsQuery')
        ->and($queries['posts']['permission'])->toBe('blog.posts.view')
        ->and($queries['posts']['middleware'])->toBe(['auth:api']);
});
