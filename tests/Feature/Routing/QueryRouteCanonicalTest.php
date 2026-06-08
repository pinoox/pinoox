<?php
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Listener\QueryRouteCanonicalListener;
use Pinoox\Component\Router\QueryRouteResolver;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
function queryRouteTestHtaccessPath(): string
{
    return testProjectRoot() . '/.htaccess';
}
function queryRouteTestWithHtaccess(string $content, callable $callback): mixed
{
    $file = queryRouteTestHtaccessPath();
    $backup = is_file($file) ? file_get_contents($file) : null;
    $existed = is_file($file);
    file_put_contents($file, $content);
    try {
        return $callback();
    } finally {
        if ($existed) {
            file_put_contents($file, (string) $backup);
        } elseif (is_file($file)) {
            unlink($file);
        }
    }
}
it('treats empty htaccess as inactive rewrite', function () {
    queryRouteTestWithHtaccess('', function () {
        $_SERVER['REQUEST_URI'] = '/pinoox/';
        $_SERVER['SCRIPT_NAME'] = '/pinoox/index.php';
        $_SERVER['PATH_INFO'] = '';
        unset($_SERVER['REDIRECT_URL'], $_SERVER['REDIRECT_STATUS']);
        expect(QueryRouteResolver::rewriteAppearsActive())->toBeFalse()
            ->and(QueryRouteResolver::usesQueryRouting())->toBeTrue();
    });
});
it('detects active rewrite from a configured htaccess', function () {
    queryRouteTestWithHtaccess(<<<HTACCESS
RewriteEngine On
RewriteRule .* index.php [L]
# BEGIN pinoox
HTACCESS, function () {
        $_SERVER['REQUEST_URI'] = '/pinoox/';
        $_SERVER['SCRIPT_NAME'] = '/pinoox/index.php';
        $_SERVER['PATH_INFO'] = '';
        unset($_SERVER['REDIRECT_URL'], $_SERVER['REDIRECT_STATUS']);
        expect(QueryRouteResolver::rewriteAppearsActive())->toBeTrue();
    });
});
it('builds canonical path urls when rewrite is active', function () {
    queryRouteTestWithHtaccess(<<<HTACCESS
RewriteEngine On
RewriteRule .* index.php [L]
# BEGIN pinoox
HTACCESS, function () {
        expect(QueryRouteResolver::buildUrl('http://localhost/pinoox', '/dist/pinoox.js'))
            ->toBe('http://localhost/pinoox/dist/pinoox.js');
    });
});
it('builds query route urls when rewrite is inactive', function () {
    queryRouteTestWithHtaccess('', function () {
        $_SERVER['REQUEST_URI'] = '/pinoox/';
        $_SERVER['SCRIPT_NAME'] = '/pinoox/index.php';
        $_SERVER['PATH_INFO'] = '';
        unset($_SERVER['REDIRECT_URL'], $_SERVER['REDIRECT_STATUS']);
        expect(QueryRouteResolver::buildUrl('http://localhost/pinoox', '/dist/pinoox.js'))
            ->toBe('http://localhost/pinoox/?_pnx=%2Fdist%2Fpinoox.js');
    });
});
it('does not treat query-route path info as active rewrite', function () {
    queryRouteTestWithHtaccess('', function () {
        $_SERVER['REQUEST_URI'] = '/pinoox/?_pnx=api/v1/ping';
        $_SERVER['SCRIPT_NAME'] = '/pinoox/index.php';
        $_SERVER['PATH_INFO'] = '/api/v1/ping';
        $_SERVER['PINOOX_QUERY_ROUTE_APPLIED'] = '1';
        unset($_SERVER['REDIRECT_URL'], $_SERVER['REDIRECT_STATUS']);
        expect(QueryRouteResolver::wasApplied())->toBeTrue()
            ->and(QueryRouteResolver::rewriteAppearsActive())->toBeFalse();
    });
});
it('does not redirect query route requests when rewrite is inactive', function () {
    queryRouteTestWithHtaccess('', function () {
        $request = Request::create(
            '/pinoox/?_pnx=api/v1/ping',
            'GET',
            ['_pnx' => 'api/v1/ping'],
            [],
            [],
            [
                'SCRIPT_NAME' => '/pinoox/index.php',
                'REQUEST_URI' => '/pinoox/?_pnx=api/v1/ping',
                'HTTP_HOST' => 'localhost',
            ],
        );
        $event = new RequestEvent(
            test()->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
        (new QueryRouteCanonicalListener())->onKernelRequest($event);
        expect($event->hasResponse())->toBeFalse();
    });
});
it('redirects query route requests to canonical paths when rewrite is active', function () {
    queryRouteTestWithHtaccess(<<<HTACCESS
RewriteEngine On
RewriteRule .* index.php [L]
# BEGIN pinoox
HTACCESS, function () {
        $request = Request::create(
            '/pinoox/?_pnx=dist/pinoox.js',
            'GET',
            ['_pnx' => 'dist/pinoox.js'],
            [],
            [],
            [
                'SCRIPT_NAME' => '/pinoox/index.php',
                'REQUEST_URI' => '/pinoox/?_pnx=dist/pinoox.js',
                'HTTP_HOST' => 'localhost',
            ],
        );
        $event = new RequestEvent(
            test()->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
        (new QueryRouteCanonicalListener())->onKernelRequest($event);
        expect($event->hasResponse())->toBeTrue()
            ->and($event->getResponse()->getStatusCode())->toBe(301)
            ->and($event->getResponse()->headers->get('Location'))
            ->toContain('/dist/pinoox.js');
    });
});
it('redirects api query route without leading slash to canonical path when rewrite is active', function () {
    queryRouteTestWithHtaccess(<<<HTACCESS
RewriteEngine On
RewriteRule .* index.php [L]
# BEGIN pinoox
HTACCESS, function () {
        $request = Request::create(
            '/pinoox/?_pnx=api/v1/ping',
            'GET',
            ['_pnx' => 'api/v1/ping'],
            [],
            [],
            [
                'SCRIPT_NAME' => '/pinoox/index.php',
                'REQUEST_URI' => '/pinoox/?_pnx=api/v1/ping',
                'HTTP_HOST' => 'localhost',
            ],
        );
        $event = new RequestEvent(
            test()->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
        (new QueryRouteCanonicalListener())->onKernelRequest($event);
        expect($event->hasResponse())->toBeTrue()
            ->and($event->getResponse()->getStatusCode())->toBe(301)
            ->and($event->getResponse()->headers->get('Location'))
            ->toContain('/api/v1/ping');
    });
});
it('preserves unrelated query parameters in canonical redirects', function () {
    queryRouteTestWithHtaccess(<<<HTACCESS
RewriteEngine On
RewriteRule .* index.php [L]
# BEGIN pinoox
HTACCESS, function () {
        $request = Request::create(
            '/pinoox/?_pnx=dist/pinoox.js&lang=fa',
            'GET',
            ['_pnx' => 'dist/pinoox.js', 'lang' => 'fa'],
            [],
            [],
            [
                'SCRIPT_NAME' => '/pinoox/index.php',
                'REQUEST_URI' => '/pinoox/?_pnx=dist/pinoox.js&lang=fa',
                'HTTP_HOST' => 'localhost',
            ],
        );
        $canonical = QueryRouteResolver::canonicalUrlForRequest($request);
        expect($canonical)->toBe('http://localhost/pinoox/dist/pinoox.js?lang=fa');
    });
});

