import {translate} from '@utils/helpers/managerLang.js';
import {resolveAppDisplayLabel} from '@utils/helpers/appDisplayLabel.js';

export function normalizeRoutePath(rawPath) {
    const value = String(rawPath ?? '').trim();

    if (!value || value === '/') {
        return '/';
    }

    return value.startsWith('/') ? value : `/${value}`;
}

export function buildRouteDuplicateMessage(route, appStore) {
    if (!route) {
        return '';
    }

    const app = appStore.fetchAppByPackage(route.package);

    return translate('route_path_duplicate').replace(
        '{app}',
        resolveAppDisplayLabel(app, route.package),
    );
}

export function validateRoutePath(rawPath, routeStore, appStore) {
    const trimmed = String(rawPath ?? '').trim();

    if (!trimmed) {
        return {
            valid: false,
            message: 'لطفاً آدرس را وارد کنید.',
        };
    }

    const path = normalizeRoutePath(trimmed);
    const duplicate = routeStore.fetchRouteByPath(path);

    if (duplicate) {
        return {
            valid: false,
            message: buildRouteDuplicateMessage(duplicate, appStore),
        };
    }

    return {
        valid: true,
        message: '',
        path,
    };
}
