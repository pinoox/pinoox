import PageDesktop from '@/views/pages/desktop/desktop-view.vue';

import PageControl from '@/views/pages/control/control-view.vue';
import PageProfile from '@/views/pages/control/profile/profile-view.vue';

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