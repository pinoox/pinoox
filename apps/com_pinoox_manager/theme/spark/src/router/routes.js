import PageDesktop from '@/views/pages/desktop/desktop-view.vue';

import PageLogin from '@/views/pages/account/login.vue';

import ControlPanelView from '@/views/pages/control/control-panel-view.vue';
import {createControlPanelChildRoutes} from '@/router/controlPanelRoutes.js';
import PageAppView from '@/views/pages/app-view/app-view.vue';

import MarketView from '@/views/pages/market/market-view.vue';

export const routes = [
    {
        path: '/',
        name: 'desktop',
        component: PageDesktop,
        meta: {
            showDock: true,
        },
    },
    {
        path: '/demo/:package_name',
        name: 'app-view',
        component: PageAppView,
        props: true,
        meta: {
            toolbar: false,
            showDock: false,
        },
    },
    {
        path: '/control',
        component: ControlPanelView,
        meta: {
            toolbar: false,
            showDock: false,
        },
        children: createControlPanelChildRoutes(),
    },
    {
        path: '/app-manager/:package_name/:section?',
        redirect: (to) => {
            const section = to.params.section || 'details';

            return `/control/apps/${to.params.package_name}/${section}`;
        },
    },
    {
        path: '/market',
        name: 'market-home',
        component: MarketView,
        meta: {
            showDock: true,
        },
    },
    {
        path: '/market/:package_name',
        redirect: {name: 'market-home'},
    },
    {
        path: '/login',
        name: 'login',
        component: PageLogin,
        meta: {
            single: true,
        },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];
