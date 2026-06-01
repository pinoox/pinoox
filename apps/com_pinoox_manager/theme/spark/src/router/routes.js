import PageDesktop from '@/views/pages/desktop/desktop-view.vue';

import PageLogin from '@/views/pages/account/login.vue';

import PageControl from '@/views/pages/control/control-view.vue';
import PageProfile from '@/views/pages/control/profile/profile-home.vue';
import PageAppearance from '@/views/pages/control/appearance/appearance-home.vue';
import PageApps from '@/views/pages/control/apps/apps-home.vue';
import PageAppsManual from '@/views/pages/control/apps/apps-manual.vue';
import PageRoutes from '@/views/pages/control/routes/routes-home.vue';
import PagePincore from '@/views/pages/control/pincore/pincore-home.vue';
import PageWidgets from '@/views/pages/control/widgets/widgets-home.vue';
import PageWidgetDetail from '@/views/pages/control/widgets/widget-detail.vue';

import AppManagerView from '@/views/pages/app-manager/app-manager-view.vue';
import AppDetails from '@/views/pages/app-manager/app-details.vue';
import AppConfig from '@/views/pages/app-manager/app-config.vue';
import AppUsers from '@/views/pages/app-manager/app-users.vue';
import AppTemplates from '@/views/pages/app-manager/app-templates.vue';

import MarketHome from '@/views/pages/market/market-home.vue';
import MarketDetails from '@/views/pages/market/market-details.vue';

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
        path: '/control',
        component: PageControl,
        children: [
            {
                path: '',
                redirect: { name: 'appearance' },
            },
            {
                path: 'appearance',
                name: 'appearance',
                component: PageAppearance,
            },
            {
                path: 'widgets',
                name: 'widgets',
                component: PageWidgets,
            },
            {
                path: 'widgets/:id',
                name: 'widget-detail',
                component: PageWidgetDetail,
                props: true,
            },
            {
                path: 'apps',
                name: 'apps',
                component: PageApps,
            },
            {
                path: 'apps/manual',
                name: 'apps-manual',
                component: PageAppsManual,
            },
            {
                path: 'routes',
                name: 'routes',
                component: PageRoutes,
            },
            {
                path: 'profile',
                name: 'profile',
                component: PageProfile,
            },
            {
                path: 'pincore',
                name: 'pincore',
                component: PagePincore,
            },
        ],
    },
    {
        path: '/app-manager/:package_name',
        component: AppManagerView,
        props: true,
        children: [
            {
                path: 'details',
                name: 'app-details',
                component: AppDetails,
                props: true,
            },
            {
                path: 'config',
                name: 'app-config',
                component: AppConfig,
                props: true,
            },
            {
                path: 'users',
                name: 'app-users',
                component: AppUsers,
                props: true,
            },
            {
                path: 'templates',
                name: 'app-templates',
                component: AppTemplates,
                props: true,
            },
            {
                path: '',
                redirect: to => ({ name: 'app-details', params: to.params }),
            },
        ],
    },
    {
        path: '/market',
        name: 'market-home',
        component: MarketHome,
    },
    {
        path: '/market/:package_name',
        name: 'market-details',
        component: MarketDetails,
        props: true,
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
