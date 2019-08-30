import Lang from '../vue/lang.vue';
import Setup from '../vue/setup.vue';
import Rules from '../vue/rules.vue';
import Prerequisites from '../vue/prerequisites.vue';
import DB from '../vue/db.vue';
import User from '../vue/user.vue';

export const routes = [
    {
        path: PINOOX.URL.BASE + 'lang',
        name: 'lang',
        component: Lang
    },
    {
        path: PINOOX.URL.BASE + 'setup',
        name: 'setup',
        component: Setup
    },
    {
        path: PINOOX.URL.BASE + 'rules',
        name: 'rules',
        component: Rules
    },
    {
        path: PINOOX.URL.BASE + 'prerequisites',
        name: 'prerequisites',
        component: Prerequisites
    },
    {
        path: PINOOX.URL.BASE + 'db',
        name: 'db',
        component: DB
    },
    {
        path: PINOOX.URL.BASE + 'user',
        name: 'user',
        component: User
    },
];