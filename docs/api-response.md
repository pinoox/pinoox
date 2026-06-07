# Pinoox API Response Standard

All JSON API endpoints in Pinoox apps use one envelope format.  
Core implementation: `pincore/Component/Http/Api/`.

## Success envelope

```json
{
  "success": true,
  "data": {},
  "message": "OK",
  "meta": {}
}
```

| Field | Type | Description |
|-------|------|-------------|
| `success` | `bool` | Always `true` |
| `data` | `mixed` | Payload (object, array, scalar, or `null`) |
| `message` | `string` | Human-readable status; defaults to `OK` |
| `meta` | `object` | Optional pagination, counts, etc. |

## Error envelope

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "The email field is required.",
    "details": {}
  }
}
```

| Field | Type | Description |
|-------|------|-------------|
| `success` | `bool` | Always `false` |
| `error.code` | `string` | Stable machine-readable code |
| `error.message` | `string` | Human-readable explanation |
| `error.details` | `object` | Extra context (validation fields, debug hints) |

---

## Backend — new endpoints

Extend the base API controller:

```php
use Pinoox\Component\Kernel\Controller\ApiController as BaseApiController;
use Pinoox\Component\Http\Api\PayloadResource;

class PostController extends BaseApiController
{
    public function index(): JsonResponse
    {
        return $this->ok(PostModel::all());
    }

    public function show(int $id): JsonResponse
    {
        $post = PostModel::find($id);

        if ($post === null) {
            return $this->fail('NOT_FOUND', 'post.not_found', status: 404);
        }

        return $this->resource(new PostResource($post), 'post.loaded');
    }
}
```

### Helpers

| Method | Use case |
|--------|----------|
| `ok($data, $message, $meta, $status, $translate)` | Generic success |
| `fail($code, $message, $details, $status, $translate)` | Errors |
| `resource(ApiResource $resource, ...)` | Typed `data` via resource class |

### Direct response builder

```php
use Pinoox\Component\Http\Api\ApiResponse;

return ApiResponse::success(['connected' => true], 'connect', translate: true);
return ApiResponse::error('DB_CONNECTION_FAILED', 'disconnect', status: 422);
```

### ApiResource

```php
use Pinoox\Component\Http\Api\ApiResource;

final class PostResource extends ApiResource
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

For arbitrary arrays use `PayloadResource`:

```php
return $this->resource(new PayloadResource(['items' => $checks]));
```

---

## Backend — manager legacy controllers

`com_pinoox_manager` still calls `message()` / `error()` on `Controller\Api`.  
That base class maps legacy calls to the standard envelope:

| Legacy call | New envelope |
|-------------|--------------|
| `message($profile)` | `data = $profile` |
| `message('saved')` | `data = null`, `message = saved` |
| `message('invalid', false)` | `data = false`, `message = invalid` |
| `message('logged in', $token)` | `data = $token`, `message = logged in` |
| `error('not_found', 404)` | `error.code = API_ERROR`, HTTP 404 |

Prefer `ok()` / `fail()` / `resource()` for new manager endpoints.

---

## Frontend (Vue / axios)

Copy `apiEnvelope.js` from any migrated theme, then unwrap responses:

```js
import { unwrapApiResponse, readApiErrorMessage } from '@utils/apiEnvelope.js';

const token = unwrapApiResponse(await authAPI.login(credentials));

http.interceptors.response.use(
  (response) => response,
  (error) => Promise.reject(readApiErrorMessage(error)),
);
```

`unwrapResponse()` in `apiHelper.js` supports both the new envelope and legacy `{ result, message }`.

### Installer example

```js
// apps/com_pinoox_installer/theme/magic/src/utils/apiEnvelope.js
import { unwrapApiResponse } from '@utils/apiEnvelope.js';

const diagnostics = unwrapApiResponse(await http.get('/bootstrap/diagnostics'));
```

### Manager example

```js
import { unwrapResponse } from '@utils/helpers/apiHelper.js';

const apps = unwrapResponse(await appAPI.get('installed'));
```

---

## Error codes (recommended)

Use uppercase snake case and keep them stable:

| Code | HTTP | Meaning |
|------|------|---------|
| `VALIDATION_FAILED` | 422 | Request validation failed |
| `NOT_FOUND` | 404 | Resource missing |
| `ACCESS_DENIED` | 401 / 403 | Auth / permission |
| `DB_CONNECTION_FAILED` | 422 | Database unreachable |
| `SETUP_FAILED` | 500 | Installer setup error |
| `API_ERROR` | 422 | Generic manager legacy error |

---

## Apps status

| App | API base | Frontend unwrap |
|-----|----------|-----------------|
| `com_pinoox_installer` | `BaseApiController` + Resources | `theme/magic/src/utils/apiEnvelope.js` |
| `com_pinoox_manager` | `Api extends BaseApiController` (legacy helpers) | `theme/spark/src/utils/apiEnvelope.js` |
| `com_pinoox_welcome` | No JSON API | — |
| `com_pinoox_comingsoon` | No JSON API | — |

---

## Tests

```bash
vendor/bin/pest tests/Feature/HttpApiResponseTest.php
vendor/bin/pest tests/Feature/ManagerApiEnvelopeTest.php
vendor/bin/pest tests/Feature/InstallerApiEnvelopeTest.php
```
