# Pinoox Router

Pinoox Router is the routing layer used by apps, portals, API routes, and internal request dispatching.

It supports the existing classic route style and the newer fluent Builder style.

The old style remains supported for backward compatibility.

## Portal

Most app code uses the Router through the portal:

```php
use Pinoox\Portal\Router;
```

## Classic Route Style

The existing route API is still supported:

```php
Router::add('/posts', [PostController::class, 'index'], 'posts.index', ['GET']);
```

Shortcut methods are also supported:

```php
Router::get('/posts', [PostController::class, 'index'], 'posts.index');

Router::post('/posts', [PostController::class, 'store'], 'posts.store');

Router::put('/posts/{id}', [PostController::class, 'update'], 'posts.update');

Router::patch('/posts/{id}', [PostController::class, 'patch'], 'posts.patch');

Router::delete('/posts/{id}', [PostController::class, 'destroy'], 'posts.destroy');
```

## Fluent Builder Style

The new recommended style is:

```php
Router::route('/posts', [PostController::class, 'index'])
    ->get()
    ->name('posts.index')
    ->register();
```

POST example:

```php
Router::route('/posts', [PostController::class, 'store'])
    ->post()
    ->name('posts.store')
    ->register();
```

PUT example:

```php
Router::route('/posts/{id}', [PostController::class, 'update'])
    ->put()
    ->name('posts.update')
    ->register();
```

DELETE example:

```php
Router::route('/posts/{id}', [PostController::class, 'destroy'])
    ->delete()
    ->name('posts.destroy')
    ->register();
```

## Builder Entry Points

Use `Router::route()` for simple routes:

```php
Router::route('/posts', [PostController::class, 'index'])
    ->get()
    ->register();
```

Use `Router::builder()` when you want to build everything step by step:

```php
Router::builder()
    ->path('/posts')
    ->action([PostController::class, 'index'])
    ->methods('GET')
    ->name('posts.index')
    ->register();
```

## Builder Methods

`path(string $path)`

Sets route path.

```php
->path('/posts')
```

`action(array|string|Closure $action)`

Sets route action.

```php
->action([PostController::class, 'index'])
```

`name(string $name)`

Sets route name.

```php
->name('posts.index')
```

`methods(array|string $methods)`

Sets HTTP methods.

```php
->methods(['GET', 'POST'])
```

`method(string $method)`

Sets a single HTTP method.

```php
->method('GET')
```

HTTP shortcuts:

```php
->get()
->post()
->put()
->patch()
->delete()
->options()
->head()
```

`defaults(array $defaults)`

Sets route defaults.

```php
->defaults(['_custom' => true])
```

`filters(array $filters)`

Sets route requirements/filters.

```php
->filters(['id' => '\d+'])
```

`data(array $data)`

Stores custom route metadata.

```php
->data(['owner' => 'blog'])
```

`flow(array|string $flow)`

Adds Pinoox Flow middleware.

```php
->flow('auth:api')
```

`flows(array $flows)`

Replaces current flows.

```php
->flows(['auth:api', 'verified'])
```

`middleware(array|string $middleware)`

Alias for `flow()`.

```php
->middleware('auth:api')
```

`tags(array $tags)`

Adds route tags.

```php
->tags(['api', 'blog'])
```

`priority(?int $priority)`

Sets route priority.

```php
->priority(10)
```

`register()`

Registers the route into the current Router.

```php
->register()
```

## Flow And Middleware

Pinoox has a native request pipeline concept called `Flow`.

For developer convenience, Router Builder also supports the common name `middleware`.

These two are equivalent:

```php
Router::route('/profile', [ProfileController::class, 'show'])
    ->get()
    ->flow('auth:api')
    ->register();
```

```php
Router::route('/profile', [ProfileController::class, 'show'])
    ->get()
    ->middleware('auth:api')
    ->register();
```

Both are stored internally as Router `flows`.

Duplicate flows are automatically removed:

```php
Router::route('/profile', [ProfileController::class, 'show'])
    ->get()
    ->middleware('auth:api')
    ->flow('auth:api')
    ->flow('verified')
    ->register();
```

Final internal flows:

```php
['auth:api', 'verified']
```

## Collections

Router collections are still supported.

```php
Router::collection('/admin', function ($router) {
    $router->get('/users', [UserController::class, 'index'], 'users.index');
});
```

With prefix name:

```php
Router::collection(
    path: '/admin',
    routes: function ($router) {
        $router->get('/users', [UserController::class, 'index'], 'users.index');
    },
    prefixName: 'admin.'
);
```

Generated route name:

```text
admin.users.index
```

## Loading Route Files

Pinoox can load route files:

```php
Router::collection('/web', 'routes.php');
```

If the file path is not absolute, Pinoox resolves it from the current app path.

## Route Names

Route names are used for URL generation.

```php
Router::get('/posts', [PostController::class, 'index'], 'posts.index');
```

Generate URL:

```php
$url = Router::path('posts.index');
```

With parameters:

```php
$url = Router::path('posts.show', ['id' => 10]);
```

## Matching Routes

Match by path:

```php
$attributes = Router::match('/posts');
```

Match current request:

```php
$attributes = Router::matchRequest($request);
```

## Route Metadata

Builder supports route metadata through `data()`:

```php
Router::route('/posts', [PostController::class, 'index'])
    ->get()
    ->data([
        'permission' => 'blog.posts.view',
        'description' => 'Get posts',
    ])
    ->register();
```

API routes use this internally to store normalized API metadata.

## Tags

Tags can classify routes:

```php
Router::route('/posts', [PostController::class, 'index'])
    ->get()
    ->tags(['api', 'blog'])
    ->register();
```

API routes are automatically tagged with:

```text
api
app-api
{package}
{version}
```

## Priority

Priority controls route matching order.

```php
Router::route('/posts/special', [PostController::class, 'special'])
    ->get()
    ->priority(100)
    ->register();
```

Higher priority routes are matched earlier.

## Recommended Router Style

For new code, prefer:

```php
Router::route('/posts', [PostController::class, 'index'])
    ->get()
    ->name('posts.index')
    ->flow('auth:api')
    ->register();
```

For API-style code, `middleware()` is also acceptable:

```php
Router::route('/posts', [PostController::class, 'index'])
    ->get()
    ->name('posts.index')
    ->middleware('auth:api')
    ->register();
```

## Backward Compatibility

The classic Router API remains supported:

```php
Router::add();
Router::get();
Router::post();
Router::collection();
Router::action();
```

The Builder API is additive and does not remove the previous routing style.

## Practical Examples

Simple GET route:

```php
Router::route('/dashboard', [DashboardController::class, 'index'])
    ->get()
    ->name('dashboard')
    ->register();
```

Route with validation filter:

```php
Router::route('/users/{id}', [UserController::class, 'show'])
    ->get()
    ->name('users.show')
    ->filters(['id' => '\d+'])
    ->register();
```

Route with flow:

```php
Router::route('/account', [AccountController::class, 'index'])
    ->get()
    ->flow('auth')
    ->register();
```

Route with middleware alias:

```php
Router::route('/api/me', [MeController::class, 'show'])
    ->get()
    ->middleware('auth:api')
    ->register();
```

Route with metadata:

```php
Router::route('/posts', [PostController::class, 'index'])
    ->get()
    ->name('posts.index')
    ->data([
        'permission' => 'blog.posts.view',
        'description' => 'Get posts',
    ])
    ->register();
```

## Best Practices

- Use `Router::route()` for new routes.
- Use `flow()` when writing Pinoox-native code.
- Use `middleware()` when writing API-oriented code.
- Keep route names stable.
- Use `data()` for permission, docs, and app metadata.
- Keep old route style for existing apps until they are naturally refactored.
- Do not remove classic route methods until a major version explicitly deprecates them.
