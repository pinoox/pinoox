import Lang from '@/views/Lang.vue'
import Setup from '@/views/Setup.vue'
import Rules from '@/views/Rules.vue'
import Prerequisites from '@/views/Prerequisites.vue'
import Db from '@/views/Db.vue'
import User from '@/views/User.vue'

export const routes = [
    {
        path: '/lang',
        name: 'lang',
        component: Lang,
    },
    {
        path: '/setup',
        name: 'setup',
        component: Setup,
    },
    {
        path: '/rules',
        name: 'rules',
        component: Rules,
        props: true,
    },
    {
        path: '/prerequisites',
        name: 'prerequisites',
        component: Prerequisites,
        props: true,
    },
    {
        path: '/db',
        name: 'db',
        component: Db,
        props: true,
    },
    {
        path: '/user',
        name: 'user',
        component: User,
        props: true,
    },
]
