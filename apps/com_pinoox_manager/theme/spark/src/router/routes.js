import PageDesktop from '@/views/pages/desktop/desktop-view.vue';

import PageControl from '@/views/pages/control/control-view.vue';
import PageProfile from '@/views/pages/control/profile/profile-home.vue';
import PageAppearance from '@/views/pages/control/appearance/appearance-home.vue';
import PageApps from '@/views/pages/control/apps/apps-home.vue';

// Define routes
export const routes = [
    {
        path: '/',
        name: 'desktop',
        component: PageDesktop,
    },
    {
        path: '/control',
        component: PageControl,
        children: [
            {
                path: 'appearance',
                name: 'appearance',
                component: PageAppearance
            },
            {
                path: 'apps',
                name: 'apps',
                component: PageApps
            },
            {
                path: 'profile',
                name: 'profile',
                component: PageProfile
            }
        ]

    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/'
    }


];