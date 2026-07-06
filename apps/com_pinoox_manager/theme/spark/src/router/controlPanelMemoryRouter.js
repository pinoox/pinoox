import {createRouter, createMemoryHistory} from 'vue-router';
import {createControlPanelChildRoutes} from '@/router/controlPanelRoutes.js';

let memoryRouter = null;

function createControlPanelMemoryRoutes() {
    const childRoutes = createControlPanelChildRoutes();

    return [
        ...childRoutes.map((route) => ({
            ...route,
            path: route.path === '' ? '/' : `/${route.path}`,
        })),
        {
            path: '/:pathMatch(.*)*',
            redirect: {name: 'apps'},
        },
    ];
}

export function getControlPanelMemoryRouter() {
    if (!memoryRouter) {
        memoryRouter = createRouter({
            history: createMemoryHistory('/apps'),
            routes: createControlPanelMemoryRoutes(),
        });
    }

    return memoryRouter;
}

export function resolveMemoryRoutePage(route) {
    const record = route.matched[route.matched.length - 1];

    if (!record?.components?.default) {
        return {component: null, props: {}};
    }

    const propsOption = record.props;
    let props = {};

    if (propsOption === true) {
        props = route.params;
    } else if (typeof propsOption === 'function') {
        props = propsOption(route);
    } else if (propsOption && typeof propsOption.default === 'function') {
        props = propsOption.default(route);
    } else if (propsOption?.default) {
        props = propsOption.default;
    }

    return {
        component: record.components.default,
        props,
    };
}

export function normalizeControlPanelPath(path = '/control/apps') {
    const normalized = String(path ?? '').trim();

    if (!normalized) {
        return '/control/apps';
    }

    if (normalized.startsWith('/control')) {
        return normalized;
    }

    return `/control/${normalized.replace(/^\//, '')}`;
}

export function toMemoryRouterPath(path = '/control/apps') {
    const normalized = normalizeControlPanelPath(path);

    if (normalized === '/control' || normalized === '/control/') {
        return '/apps';
    }

    const memoryPath = normalized.replace(/^\/control/, '') || '/';

    return memoryPath === '/' ? '/apps' : memoryPath;
}

export async function syncControlPanelMemoryRouter(path = '/control/apps') {
    const router = getControlPanelMemoryRouter();
    const target = toMemoryRouterPath(path);

    if (router.currentRoute.value.path !== target) {
        await router.replace(target);
    }
}

export function isControlPanelMemoryPath(path) {
    return String(path ?? '').startsWith('/control');
}
