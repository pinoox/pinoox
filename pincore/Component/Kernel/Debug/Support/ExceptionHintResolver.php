<?php

namespace Pinoox\Component\Kernel\Debug\Support;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ExceptionHintResolver
{
    public static function resolve(FlattenException $exception, array $context = []): array
    {
        $message = $exception->getMessage();
        $messageLower = strtolower($message);
        $class = $exception->getClass();
        $portal = $context['portal'] ?? [];
        $route = $context['route'] ?? [];
        $request = $context['request'] ?? [];
        $package = (string) ($context['package'] ?? '');

        $hints = array_merge(
            self::portalUndefinedMethodHints($message, $portal, $route),
            self::routeHints($class, $messageLower, $route, $request, $package),
            self::autoloadHints($class, $message, $messageLower),
            self::databaseHints($messageLower),
            self::validationHints($class, $messageLower),
            self::templateHints($messageLower, $class),
            self::argumentHints($class, $messageLower),
            self::filesystemHints($messageLower),
            self::httpHints($exception, $class),
        );

        $hints = self::dedupe($hints);
        $hints = self::sort($hints);

        if ($hints === []) {
            $hints[] = self::fallbackHint($portal, $route);
        }

        return array_slice($hints, 0, 6);
    }

    private static function portalUndefinedMethodHints(string $message, array $portal, array $route): array
    {
        if (!str_contains(strtolower($message), 'undefined method') && !str_contains(strtolower($message), 'did you mean to call')) {
            return [];
        }

        $hints = [];
        $method = (string) ($portal['method'] ?? '');
        $suggestion = (string) ($portal['suggestion'] ?? '');
        $call = (string) ($portal['call'] ?? '');
        $location = self::locationLabel($portal);
        $portalName = (string) ($portal['portal'] ?? 'Portal');

        if ($suggestion !== '' && $call !== '') {
            $fixedCall = preg_replace('/::' . preg_quote($method, '/') . '\s*\(/', '::' . $suggestion . '(', $call, 1) ?? $call;
            $hints[] = self::hint(
                priority: 'high',
                title: 'Fix the Portal method typo',
                summary: "{$portalName}::{$method}() does not exist on the underlying service.",
                steps: array_values(array_filter([
                    $location !== '' ? "Open {$location} — this is where you wrote {$call}" : "Find the line where you call {$call}",
                    "Rename {$method}() to {$suggestion}()",
                    'Save the file and reload this request',
                ])),
                fix: $fixedCall !== $call ? $fixedCall : "{$portalName}::{$suggestion}(…)",
                location: $location,
                docs: ExceptionContext::docsUrl('portal facade view'),
            );
        } else {
            $hints[] = self::hint(
                priority: 'high',
                title: 'Undefined Portal method',
                summary: 'The Portal facade forwarded a call to a method that does not exist on the service class.',
                steps: array_values(array_filter([
                    $location !== '' ? "Inspect {$location}" : 'Use the Portal call block above',
                    $method !== '' ? "Confirm {$portalName}::{$method}() is defined on the service or Portal" : 'Check available methods on the target service class',
                    'Prefer documented Portal methods or add the method to the service',
                ])),
                location: $location,
                docs: ExceptionContext::docsUrl('portal facade'),
            );
        }

        if (!empty($portal['via_portal'])) {
            $hints[] = self::hint(
                priority: 'medium',
                title: 'Why Portal.php appears in the stack',
                summary: 'Pinoox Portals delegate static calls to container services — the real bug is in your call site.',
                steps: [
                    'Ignore Portal.php internal frames (already hidden in the trace)',
                    'Use “Jump to your code” in the Exception tab',
                    'Fix the highlighted route/action or controller line',
                ],
                docs: ExceptionContext::docsUrl('portal architecture'),
            );
        }

        if (!empty($route['action_source']['relative_file'])) {
            $actionLoc = self::locationLabel($route['action_source']);
            $hints[] = self::hint(
                priority: 'medium',
                title: 'Route action owns this call',
                summary: 'This request runs through a named action registered in your routes files.',
                steps: [
                    "Action file: {$actionLoc}",
                    !empty($route['action_ref']) ? "Route references {$route['action_ref']}" : 'Trace the @action reference in routes/web.php or routes/api.php',
                    'Fix the closure or controller binding inside the action definition',
                ],
                location: $actionLoc,
                docs: ExceptionContext::docsUrl('router actions'),
            );
        }

        return $hints;
    }

    private static function routeHints(string $class, string $messageLower, array $route, array $request, string $package): array
    {
        $isRouteMissing = is_a($class, ResourceNotFoundException::class, true)
            || is_a($class, NotFoundHttpException::class, true)
            || str_contains($messageLower, 'no route found')
            || str_contains($messageLower, 'route not found');

        if (!$isRouteMissing) {
            return [];
        }

        $method = strtoupper((string) ($request['method'] ?? 'GET'));
        $path = (string) ($request['path'] ?? '/');
        $packageLabel = $package !== '' ? $package : 'active app';

        return [self::hint(
            priority: 'high',
            title: 'Route not found',
            summary: "No matching route for {$method} {$path}.",
            steps: [
                "Confirm {$packageLabel} is the app handling this URL",
                "Check apps/{package}/routes/web.php and routes/api.php",
                "Register a route for {$method} {$path} or fix the request URL",
                !empty($route['name']) ? "Last matched route was {$route['name']} — verify method/path/filters" : 'Verify HTTP method (GET/POST/…) matches the route definition',
            ],
            docs: ExceptionContext::docsUrl('router routes'),
        )];
    }

    private static function autoloadHints(string $class, string $message, string $messageLower): array
    {
        if (!str_contains($class, 'ClassNotFoundError') && !str_contains($messageLower, 'class not found') && !str_contains($messageLower, 'interface not found')) {
            return [];
        }

        $missing = '';
        if (preg_match('/Class ["\']([^"\']+)["\'] not found/i', $message, $m)) {
            $missing = $m[1];
        }

        return [self::hint(
            priority: 'high',
            title: 'Class autoload issue',
            summary: $missing !== '' ? "PHP cannot autoload {$missing}." : 'A referenced class was not found by the autoloader.',
            steps: array_values(array_filter([
                'Verify namespace and folder match PSR-4 in composer.json',
                $missing !== '' ? "Ensure {$missing}.php exists under apps/ or pincore/" : 'Ensure the class file exists and filename matches the class name',
                'Run composer dump-autoload -o from the project root',
            ])),
            docs: ExceptionContext::docsUrl('composer autoload namespace'),
        )];
    }

    private static function databaseHints(string $messageLower): array
    {
        if (!str_contains($messageLower, 'sqlstate')
            && !str_contains($messageLower, 'database')
            && !str_contains($messageLower, 'connection refused')
            && !str_contains($messageLower, 'base table or view not found')
        ) {
            return [];
        }

        $steps = [
            'Check DB host, database name, user, and password in .env or pincore/config/database.config.php',
            'Ensure MySQL/MariaDB is running and reachable from PHP',
        ];

        if (str_contains($messageLower, 'base table or view not found')) {
            $steps[] = 'Run pending migrations: php pinoox migrate';
            $steps[] = 'Confirm the table name uses the correct app prefix in Model/Table.php';
        }

        if (str_contains($messageLower, 'access denied')) {
            $steps[] = 'Verify database credentials and user privileges for the schema';
        }

        return [self::hint(
            priority: 'high',
            title: 'Database error',
            summary: 'The query failed because of connection, schema, or credentials.',
            steps: $steps,
            docs: ExceptionContext::docsUrl('database migration'),
        )];
    }

    private static function validationHints(string $class, string $messageLower): array
    {
        if (!str_contains($messageLower, 'validation') && !str_contains($class, 'ValidationException')) {
            return [];
        }

        return [self::hint(
            priority: 'medium',
            title: 'Validation failed',
            summary: 'Submitted data did not pass the validation rules for this endpoint.',
            steps: [
                'Open the Context tab and compare POST/JSON payload with expected fields',
                'Inspect validation rules in the controller, FormRequest, or #[Validate] attributes',
                'Return field-specific errors instead of throwing when possible',
            ],
            docs: ExceptionContext::docsUrl('validation request api'),
        )];
    }

    private static function templateHints(string $messageLower, string $class): array
    {
        if (!str_contains($messageLower, 'twig')
            && !str_contains($messageLower, 'template')
            && !str_contains($messageLower, 'render')
            && !str_contains($class, 'Twig')
        ) {
            return [];
        }

        if (!str_contains($messageLower, 'not found') && !str_contains($messageLower, 'unable to find')) {
            return [];
        }

        return [self::hint(
            priority: 'medium',
            title: 'Template not found',
            summary: 'Twig/View could not locate the requested template file.',
            steps: [
                'Confirm the template name matches a file under apps/{package}/theme/{theme}/',
                'Check active theme in app.php and theme folder spelling',
                'Use View::exists() during development to verify template paths',
            ],
            docs: ExceptionContext::docsUrl('view twig theme'),
        )];
    }

    private static function argumentHints(string $class, string $messageLower): array
    {
        if (!str_contains($class, 'ArgumentCountError') && !str_contains($messageLower, 'too few arguments') && !str_contains($messageLower, 'too many arguments')) {
            return [];
        }

        return [self::hint(
            priority: 'medium',
            title: 'Wrong number of arguments',
            summary: 'The called function or method received an unexpected argument count.',
            steps: [
                'Compare your call signature with the method definition in the trace',
                'Check for missing route parameters or optional defaults',
                'Update the action/closure or controller method parameters',
            ],
            docs: ExceptionContext::docsUrl('controller router'),
        )];
    }

    private static function filesystemHints(string $messageLower): array
    {
        if (!str_contains($messageLower, 'failed to open stream')
            && !str_contains($messageLower, 'no such file or directory')
            && !str_contains($messageLower, 'permission denied')
        ) {
            return [];
        }

        $steps = ['Verify the path exists relative to the project root or app folder'];
        if (str_contains($messageLower, 'permission denied')) {
            $steps[] = 'Fix filesystem permissions on uploads/, storage/, or the referenced directory';
        } else {
            $steps[] = 'Check Path/config references and constants such as PINOOX_BASE_PATH';
        }

        return [self::hint(
            priority: 'medium',
            title: 'Filesystem issue',
            summary: 'PHP could not read or write a required file or directory.',
            steps: $steps,
            docs: ExceptionContext::docsUrl('filesystem path upload'),
        )];
    }

    private static function httpHints(FlattenException $exception, string $class): array
    {
        if (!is_a($class, HttpExceptionInterface::class, true)) {
            return [];
        }

        if (is_a($class, NotFoundHttpException::class, true)) {
            return [];
        }

        return [self::hint(
            priority: 'low',
            title: 'HTTP ' . $exception->getStatusCode() . ' response',
            summary: 'The application intentionally aborted with an HTTP error.',
            steps: [
                'Inspect controller/Flow logic that throws this status',
                'Check auth flows, guards, and middleware conditions',
                'Validate request input before reaching the abort/throw point',
            ],
            docs: ExceptionContext::docsUrl('http flow controller'),
        )];
    }

    private static function fallbackHint(array $portal, array $route): array
    {
        $steps = [
            'Start from the “Your code” frame or the first non-vendor trace entry',
            'Compare recent edits in routes, controllers, and Portal calls',
        ];

        if (!empty($route['path'])) {
            $steps[] = 'Current route path: ' . $route['path'];
        }

        if (!empty($portal['call'])) {
            $steps[] = 'Recent Portal call: ' . $portal['call'];
        }

        $steps[] = 'Use Context and Tools tabs for request payload, headers, and cURL replay';

        return self::hint(
            priority: 'low',
            title: 'General debug checklist',
            summary: 'No specific pattern matched — use the trace and request context to narrow it down.',
            steps: $steps,
            docs: ExceptionContext::docsUrl('debug troubleshooting'),
        );
    }

    private static function hint(
        string $priority,
        string $title,
        string $summary,
        array $steps = [],
        ?string $fix = null,
        ?string $location = null,
        ?string $docs = null,
    ): array {
        return array_filter([
            'priority' => $priority,
            'title' => $title,
            'summary' => $summary,
            'steps' => $steps,
            'fix' => $fix,
            'location' => $location,
            'docs' => $docs,
        ], static fn ($value) => $value !== null && $value !== []);
    }

    private static function locationLabel(array $source): string
    {
        $file = (string) ($source['relative_file'] ?? $source['file'] ?? '');
        $line = (int) ($source['line'] ?? 0);

        if ($file === '') {
            return '';
        }

        return $line > 0 ? "{$file}:{$line}" : $file;
    }

    private static function dedupe(array $hints): array
    {
        $seen = [];
        $result = [];

        foreach ($hints as $hint) {
            $key = ($hint['title'] ?? '') . '|' . ($hint['summary'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $hint;
        }

        return $result;
    }

    private static function sort(array $hints): array
    {
        $weight = ['high' => 0, 'medium' => 1, 'low' => 2];

        usort($hints, static function (array $a, array $b) use ($weight): int {
            return ($weight[$a['priority'] ?? 'low'] ?? 9) <=> ($weight[$b['priority'] ?? 'low'] ?? 9);
        });

        return $hints;
    }
}

