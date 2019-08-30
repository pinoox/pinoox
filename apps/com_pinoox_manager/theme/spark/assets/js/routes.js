import Home from '../vue/home.vue';
import Login from '../vue/login.vue';
import Loading from '../vue/loading.vue';
import Setting from '../vue/setting/main.vue';
import SettingDashboard from '../vue/setting/dashboard.vue';
import SettingAccount from '../vue/setting/account.vue';
import SettingRouter from '../vue/setting/router.vue';
import SettingMarket from '../vue/setting/market.vue';
import SettingAbout from '../vue/setting/about.vue';

export const routes = [
    {
        path: PINOOX.URL.BASE + 'loading',
        name: 'loading',
        props:true,
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
                name: 'setting-market',
                component: SettingMarket
            },
            {
                path: 'about',
                name: 'setting-about',
                component: SettingAbout
            },
        ]
    }
];