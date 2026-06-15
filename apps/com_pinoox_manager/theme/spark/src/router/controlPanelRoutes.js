import PageProfile from '@/views/pages/control/profile/profile-home.vue';
import PageAppearanceSettings from '@/views/pages/control/settings/appearance-settings.vue';
import PageApps from '@/views/pages/control/apps/apps-home.vue';
import PageAppsManual from '@/views/pages/control/apps/apps-manual.vue';
import PageRoutes from '@/views/pages/control/routes/routes-home.vue';
import PagePincore from '@/views/pages/control/pincore/pincore-home.vue';
import PageWidgets from '@/views/pages/control/widgets/widgets-home.vue';
import PageWidgetDetail from '@/views/pages/control/widgets/widget-detail.vue';
import PageApplicationSettings from '@/views/pages/control/settings/application-settings.vue';

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
    ];
}
