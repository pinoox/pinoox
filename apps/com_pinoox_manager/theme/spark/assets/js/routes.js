/** global: PINOOX */

import Home from '../vue/home.vue';
import Login from '../vue/login.vue';
import Loading from '../vue/loading.vue';
import Setting from '../vue/setting/main.vue';
import SettingDashboard from '../vue/setting/dashboard.vue';
import SettingAccount from '../vue/setting/account.vue';
import SettingRouter from '../vue/setting/router.vue';
import SettingAbout from '../vue/setting/about.vue';
import SettingApps from '../vue/setting/app/main.vue';
import AppManager from '../vue/appManager/main.vue';
import AppsHome from '../vue/setting/app/home.vue';
import AppsManual from '../vue/setting/app/manual.vue';
import AppsFiles from '../vue/setting/app/files.vue';
import AppManagerDetails from '../vue/appManager/details.vue';
import AppManagerConfig from '../vue/appManager/config.vue';
import AppManagerUsers from '../vue/appManager/users.vue';
import AppView from '../vue/pages/app-view.vue';
import Market from '../vue/market/main.vue';
import MarketHome from '../vue/market/home.vue';
import MarketDetails from '../vue/market/details.vue';
import MarketLogin from '../vue/market/login.vue';
import MarketAccount from '../vue/market/account.vue';

export const routes = [
    {
        path: PINOOX.URL.BASE + 'loading',
        name: 'loading',
        props: true,
        component: Loading
    },
    {
        path: PINOOX.URL.BASE + 'home',
        name: 'home',
        component: Home
    },
    {
        path: PINOOX.URL.BASE + 'login',
        name: 'login',
        component: Login
    },
    {
        path: PINOOX.URL.BASE + 'setting',
        component: Setting,
        children: [
            {
                path: '',
                name: 'setting-dashboard',
                component: SettingDashboard
            },
            {
                path: 'account',
                name: 'setting-account',
                component: SettingAccount
            },
            {
                path: 'router',
                name: 'setting-router',
                component: SettingRouter
            },
            {
                path: 'apps',
                component: SettingApps,
                children: [
                    {
                        path: 'list',
                        name: 'apps-home',
                        component: AppsHome,
                    },
                    {
                        path: 'manuel',
                        name: 'apps-manual',
                        component: AppsManual,
                    },
                    {
                        path: 'files',
                        name: 'apps-files',
                        component: AppsFiles,
                    },
                ]
            },
            {
                path: 'about',
                name: 'setting-about',
                component: SettingAbout
            },
        ]
    },
    {
        path: PINOOX.URL.BASE +'market',
        component: Market,
        children: [
            {
                path: '',
                name: 'market-home',
                component: MarketHome
            },
            {
                path: 'details/:package_name',
                name: 'market-details',
                component: MarketDetails,
                props: true,
            },
            {
                path: 'login',
                name: 'market-login',
                component: MarketLogin,
                meta: {
                    requireAuth: false,
                }
            },
            {
                path: 'account',
                name: 'market-account',
                component: MarketAccount,
                meta: {
                    requireAuth: true,
                }
            },
        ]
    },
    {
        path: PINOOX.URL.BASE +'appManager/:package_name',
        component: AppManager,
        props: true,
        children: [
            {
                path: 'users',
                name: 'app-users',
                component: AppManagerUsers,
                props: true,
            },
            {
                path: 'config',
                name: 'app-config',
                component: AppManagerConfig,
                props: true,
            },
            {
                path: 'details',
                name: 'app-details',
                component: AppManagerDetails,
                props: true,
            },
        ]
    },
    {
        path: PINOOX.URL.BASE + 'demo/:package_name',
        name: 'app-view',
        component: AppView,
        props:true,
    },
];