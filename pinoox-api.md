# Pinoox API System

Pinoox provides a standard app-based API architecture. Each app can define its own REST API in a predictable, scannable, documentable structure.

REST is the primary API model. GraphQL is optional and intended for advanced apps.

## App API Structure

Each app may define API files under:

```text
apps/{package}/api/
  routes.php
  controllers/
  requests/
  resources/
  middleware/
  docs.php
```

Example:

```text
apps/com_pinoox_blog/api/routes.php
```

## REST Route Definition

`api/routes.php` must return an array:

```php
<?php

use App\com_pinoox_blog\Api\Controllers\PostController;

return [
    'version' => 'v1',
    'prefix' => 'blog',
    'middleware' => ['auth:api'],
    'routes' => [
        [
            'method' => 'GET',
            'uri' => '/posts',
            'action' => [PostController::class, 'index'],
            'name' => 'posts.index',
            'permission' => 'blog.posts.view',
            'description' => 'Get list of blog posts',
        ],
        [
            'method' => 'POST',
            'uri' => '/posts',
            'action' => [PostController::class, 'store'],
            'name' => 'posts.store',
            'permission' => 'blog.posts.create',
            'description' => 'Create new blog post',
        ],
    ],
];
```

## Standard REST URI

Pinoox registers app APIs with this pattern:

```text
/api/{version}/apps/{package}/{prefix}/{uri}
```

Example:

```text
GET  /api/v1/apps/com_pinoox_blog/blog/posts
POST /api/v1/apps/com_pinoox_blog/blog/posts
```

If `version` is not defined, `v1` is used.

## Supported HTTP Methods

Pinoox API routes support:

```text
GET
POST
PUT
PATCH
DELETE
```

## Route Fields

`method`

HTTP method. Example: `GET`.

`uri`

Route URI inside the app API prefix. Example: `/posts`.

`action`

Controller action. Example: `[PostController::class, 'index']`.

`name`

Route name. Example: `posts.index`.

`middleware`

External/developer-facing name for request pipeline layers.

Internally, Pinoox stores middleware as `flows`.

`permission`

Permission key used by the app or auth/permission layer.

`auth`

Optional auth metadata.

`rate_limit`

Optional rate limit metadata.

`request`

Optional request validation class.

`resource`

Optional response transformer/resource class.

`description`

Human-readable endpoint description.

`params`

Optional request parameters documentation.

`body`

Optional request body documentation.

`response`

Optional response sample/documentation.

## Middleware And Flow

Pinoox has a native concept called `Flow`.

For API developers, `middleware` is supported as a familiar alias.

These two are conceptually equivalent:

```php
'middleware' => ['auth:api']
```

```php
'flows' => ['auth:api']
```

Internally, API route middleware is registered as Router flows.

## Standard Success Response

```json
{
  "success": true,
  "data": {},
  "message": "OK",
  "meta": {}
}
```

Use:

```php
use Pinoox\Api\ApiResponse;

return ApiResponse::success($data, 'OK', $meta);
```

## Standard Error Response

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {}
  }
}
```

Use:

```php
use Pinoox\Api\ApiResponse;

return ApiResponse::error('VALIDATION_ERROR', 'Validation failed', $details, 422);
```

## API Registry

`Pinoox\Api\AppApiRegistry`

Responsibilities:

- Scans active Pinoox apps.
- Looks for `api/routes.php`.
- Normalizes REST API definitions.
- Adds default version, prefix, middleware, permission, request, resource, docs metadata.
- Produces full API URIs.

## API Route Loader

`Pinoox\Api\ApiRouteLoader`

Responsibilities:

- Reads normalized API definitions from `AppApiRegistry`.
- Registers routes into the existing Pinoox Router.
- Converts `middleware` into Router `flows`.
- Adds metadata such as app, version, permission, auth, rate limit, request, resource.

## API Service Provider

`Pinoox\Api\AppApiServiceProvider`

Responsibilities:

- Boots app API routes during package/provider initialization.
- Ensures API routes are registered once.
- Uses the current Pinoox Router instance.

## API Resource

`Pinoox\Api\ApiResource`

Base class for response transformers.

Example:

```php
use Pinoox\Api\ApiResource;

class PostResource extends ApiResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
        ];
    }
}
```

Collection usage:

```php
PostResource::collection($posts);
```

## API Documentation CLI

Generate REST API docs:

```bash
php pinoox api:docs
```

Markdown:

```bash
php pinoox api:docs --format=md
```

HTML:

```bash
php pinoox api:docs --format=html
```

Write output:

```bash
php pinoox api:docs --format=md --output=docs/api.md
```

Filter by app:

```bash
php pinoox api:docs --app=com_pinoox_blog
```

Filter by API version:

```bash
php pinoox api:docs --api-version=v1
```

Note: `--api-version` is used instead of `--version` because Symfony Console reserves `--version` as a global option.

## GraphQL

GraphQL is optional. It is not required for normal REST APIs.

Each app may define:

```text
apps/{package}/graphql/graphql.php
```

Example:

```php
<?php

use App\com_pinoox_blog\GraphQL\Queries\PostsQuery;
use App\com_pinoox_blog\GraphQL\Mutations\CreatePostMutation;
use App\com_pinoox_blog\GraphQL\Types\PostType;

return [
    'types' => [
        'Post' => PostType::class,
    ],
    'queries' => [
        'posts' => [
            'class' => PostsQuery::class,
            'permission' => 'blog.posts.view',
            'middleware' => ['auth:api'],
            'description' => 'List blog posts',
        ],
    ],
    'mutations' => [
        'createPost' => [
            'class' => CreatePostMutation::class,
            'permission' => 'blog.posts.create',
            'middleware' => ['auth:api'],
            'description' => 'Create a blog post',
        ],
    ],
];
```

## GraphQL Endpoint

```text
POST /graphql
```

Payload example:

```json
{
  "operation": "posts",
  "variables": {
    "limit": 10
  }
}
```

Alternative field key:

```json
{
  "field": "posts",
  "variables": {}
}
```

## GraphQL Resolver

A GraphQL query or mutation class should expose a `resolve` method:

```php
class PostsQuery
{
    public function resolve(array $variables = [], $request = null): mixed
    {
        return [
            ['id' => 1, 'title' => 'First post'],
        ];
    }
}
```

## GraphQL Registry

`Pinoox\GraphQL\GraphQLRegistry`

Responsibilities:

- Scans active apps.
- Loads `graphql/graphql.php`.
- Collects types, queries, and mutations.
- Normalizes permissions, middleware, descriptions, inputs, outputs, and examples.

## GraphQL Executor

`Pinoox\GraphQL\GraphQLExecutor`

Responsibilities:

- Handles `POST /graphql`.
- Reads requested operation.
- Finds matching query or mutation.
- Instantiates resolver class.
- Calls `resolve($variables, $request)`.
- Returns standard Pinoox API responses.

## GraphQL Documentation CLI

Generate GraphQL docs:

```bash
php pinoox graphql:docs
```

Markdown:

```bash
php pinoox graphql:docs --format=md
```

HTML:

```bash
php pinoox graphql:docs --format=html
```

Write output:

```bash
php pinoox graphql:docs --format=html --output=docs/graphql.html
```

Filter by app:

```bash
php pinoox graphql:docs --app=com_pinoox_blog
```

## Recommended API Rules

- Use REST as the default API style.
- Use GraphQL only when an app really needs flexible query composition.
- Keep app APIs inside the app package.
- Use `middleware` in public API definitions.
- Let Pinoox internally convert middleware to `Flow`.
- Always define route `name`, `description`, and `permission` for documentable APIs.
- Use request classes for validation.
- Use resource classes for stable output formatting.
