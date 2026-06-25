import PageProfile from '@/views/pages/control/profile/profile-home.vue';
import PageAppearanceSettings from '@/views/pages/control/settings/appearance-settings.vue';
import PageApps from '@/views/pages/control/apps/apps-home.vue';
import PageAppsManual from '@/views/pages/control/apps/apps-manual.vue';
import PageRoutes from '@/views/pages/control/routes/routes-home.vue';
import PagePincore from '@/views/pages/control/pincore/pincore-home.vue';
import PageWidgets from '@/views/pages/control/widgets/widgets-home.vue';
import PageWidgetDetail from '@/views/pages/control/widgets/widget-detail.vue';
import PageApplicationSettings from '@/views/pages/control/settings/application-settings.vue';
import AppManagerView from '@/views/pages/app-manager/app-manager-view.vue';
import AppDetails from '@/views/pages/app-manager/app-details.vue';
import AppConfig from '@/views/pages/app-manager/app-config.vue';
import AppUsers from '@/views/pages/app-manager/app-users.vue';
import AppTemplates from '@/views/pages/app-manager/app-templates.vue';

export function createAppManagerChildRoutes() {
    return [
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
            redirect: (to) => ({name: 'app-details', params: to.params}),
        },
    ];
}

export function createControlPanelChildRoutes() {
    return [
        {
            path: '',
            redirect: {name: 'apps'},
        },
        {
            path: 'appearance',
            redirect: {name: 'settings-appearance'},
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
            path: 'apps/manual',
            name: 'apps-manual',
            component: PageAppsManual,
        },
        {
            path: 'apps',
            name: 'apps',
            component: PageApps,
        },
        {
            path: 'apps/:package_name',
            component: AppManagerView,
            props: true,
            children: createAppManagerChildRoutes(),
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
    ];
}
