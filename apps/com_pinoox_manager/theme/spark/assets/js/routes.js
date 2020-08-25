/** global: PINOOX */

import Home from '../vue/home.vue';
import Login from '../vue/login.vue';
import Loading from '../vue/loading.vue';
import Setting from '../vue/setting/main.vue';
import SettingDashboard from '../vue/setting/dashboard.vue';
import SettingAccount from '../vue/setting/account.vue';
import SettingRouter from '../vue/setting/router.vue';
import SettingMarket from '../vue/setting/market.vue';
import SettingAbout from '../vue/setting/about.vue';
import SettingAppManager from '../vue/setting/appManager.vue';
import AppManagerHome from '../vue/setting/appManager/home.vue';
import AppManagerConfig from '../vue/setting/appManager/config.vue';
import AppManagerUsers from '../vue/setting/appManager/users.vue';
import MarketHome from '../vue/setting/market/home.vue';
import MarketDetails from '../vue/setting/market/details.vue';
import MarketLogin from '../vue/setting/market/login.vue';
import MarketAccount from '../vue/setting/market/account.vue';

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
                path: 'market',
                component: SettingMarket,
                children: [
                    {
                        path: '',
                        name: 'setting-market',
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
                path: 'appManager',
                component: SettingAppManager,
                children: [
                    {
                        path: '',
                        name: 'appManager-home',
                        component: AppManagerHome
                    },
                    {
                        path: 'config/:package_name',
                        name: 'appManager-config',
                        component: AppManagerConfig,
                        props: true,
                    },
                    {
                        path: 'users/:package_name',
                        name: 'appManager-users',
                        component: AppManagerUsers,
                        props: true,
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
];