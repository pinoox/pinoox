import {getUrl} from '@/boot.js';
import { auth } from '@/lib/auth/client.js';

export const MANAGER_TOKEN_PARAM = '__manager_token';

export function buildAppViewBasePath(packageName) {
    const base = getUrl().APP || '/';
    const normalized = base.replace(/\/+$/, '');

    return `${normalized}/app/${packageName}`;
}

export function stripManagerTokenFromUrl(href) {
    if (!href) {
        return '';
    }

    try {
        const url = new URL(href, window.location.origin);
        url.searchParams.delete(MANAGER_TOKEN_PARAM);
        const search = url.searchParams.toString();

        return `${url.origin}${url.pathname}${search ? `?${search}` : ''}${url.hash}`;
    } catch {
        return href;
    }
}

export function isAppRootRoute(route) {
    if (!route || route === '/') {
        return true;
    }

    return /^\/(\?[^#]*)?(#.*)?$/.test(route);
}

export function normalizeRouteInput(input) {
    let route = (input ?? '/').trim();

    if (!route) {
        return '/';
    }

    if (!route.startsWith('/')) {
        route = `/${route}`;
    }

    return route;
}

export function appendManagerToken(cleanUrl) {
    if (!cleanUrl) {
        return '';
    }

    try {
        const url = new URL(cleanUrl, window.location.origin);
        const token = auth.getToken();

        if (token) {
            url.searchParams.set(MANAGER_TOKEN_PARAM, token);
        }

        return url.href;
    } catch {
        return cleanUrl;
    }
}

export function buildAppViewNavigateUrl(packageName, routeInput) {
    const basePath = buildAppViewBasePath(packageName);
    const route = normalizeRouteInput(routeInput);

    let pathPart = route;
    let hash = '';
    const hashIndex = route.indexOf('#');

    if (hashIndex >= 0) {
        hash = route.slice(hashIndex);
        pathPart = route.slice(0, hashIndex);
    }

    let query = '';
    const queryIndex = pathPart.indexOf('?');

    if (queryIndex >= 0) {
        query = pathPart.slice(queryIndex);
        pathPart = pathPart.slice(0, queryIndex);
    }

    const suffix = pathPart === '/' ? '' : pathPart;
    const token = auth.getToken();
    let search = query;

    if (token) {
        const tokenParam = `${MANAGER_TOKEN_PARAM}=${encodeURIComponent(token)}`;

        search = search ? `${search}&${tokenParam}` : `?${tokenParam}`;
    }

    return `${basePath}${suffix}${search}${hash}`;
}

export function isAppFrameHref(href, packageName) {
    if (!href || !packageName || href.startsWith('about:')) {
        return false;
    }

    try {
        const url = new URL(href, window.location.origin);

        return url.pathname.includes(`/app/${packageName}`);
    } catch {
        return false;
    }
}

export function resolveAppRouteFromHref(href, packageName) {
    if (!isAppFrameHref(href, packageName)) {
        return null;
    }

    try {
        const url = new URL(href, window.location.origin);
        url.searchParams.delete(MANAGER_TOKEN_PARAM);
        const search = url.searchParams.toString();
        const query = search ? `?${search}` : '';
        const basePath = buildAppViewBasePath(packageName);
        const pathname = url.pathname;

        if (pathname.startsWith(basePath)) {
            let relative = pathname.slice(basePath.length);

            if (!relative || relative === '/') {
                return `/${query}${url.hash}`.replace(/^\/\?/, '/?');
            }

            if (!relative.startsWith('/')) {
                relative = `/${relative}`;
            }

            return relative + query + url.hash;
        }

        const pattern = new RegExp(`/app/${packageName}(/.*)?$`);
        const match = pathname.match(pattern);

        if (match) {
            const suffix = match[1] || '';

            return (suffix || '/') + query + url.hash;
        }

        return null;
    } catch {
        return null;
    }
}

export function parseAppRouteParts(route) {
    const value = route || '/';
    let pathPart = value;
    let hash = '';
    let query = '';
    const hashIndex = value.indexOf('#');

    if (hashIndex >= 0) {
        hash = value.slice(hashIndex + 1);
        pathPart = value.slice(0, hashIndex);
    }

    const queryIndex = pathPart.indexOf('?');

    if (queryIndex >= 0) {
        query = pathPart.slice(queryIndex + 1);
        pathPart = pathPart.slice(0, queryIndex);
    }

    return {
        path: pathPart || '/',
        query,
        hash,
    };
}
