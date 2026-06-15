import PageDesktop from '@/views/pages/desktop/desktop-view.vue';

import PageLogin from '@/views/pages/account/login.vue';

import ControlPanelView from '@/views/pages/control/control-panel-view.vue';
import PageProfile from '@/views/pages/control/profile/profile-home.vue';
import PageAppearanceSettings from '@/views/pages/control/settings/appearance-settings.vue';
import PageApps from '@/views/pages/control/apps/apps-home.vue';
import PageAppsManual from '@/views/pages/control/apps/apps-manual.vue';
import PageRoutes from '@/views/pages/control/routes/routes-home.vue';
import PagePincore from '@/views/pages/control/pincore/pincore-home.vue';
import PageWidgets from '@/views/pages/control/widgets/widgets-home.vue';
import PageWidgetDetail from '@/views/pages/control/widgets/widget-detail.vue';
import PageApplicationSettings from '@/views/pages/control/settings/application-settings.vue';
import PageAppView from '@/views/pages/app-view/app-view.vue';

import AppManagerView from '@/views/pages/app-manager/app-manager-view.vue';
import AppDetails from '@/views/pages/app-manager/app-details.vue';
import AppConfig from '@/views/pages/app-manager/app-config.vue';
import AppUsers from '@/views/pages/app-manager/app-users.vue';
import AppTemplates from '@/views/pages/app-manager/app-templates.vue';

import MarketHome from '@/views/pages/market/market-home.vue';

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
        children: [
            {
                path: '',
                redirect: { name: 'apps' },
            },
            {
                path: 'appearance',
                redirect: { name: 'settings-appearance' },
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
            {
                path: 'settings/appearance',
                name: 'settings-appearance',
                component: PageAppearanceSettings,
            },
            {
                path: 'settings/application',
                name: 'settings-application',
                component: PageApplicationSettings,
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
        meta: {
            showDock: true,
        },
    },
    {
        path: '/market/:package_name',
        redirect: { name: 'market-home' },
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
