/** global: PINOOX */

import Vue from 'vue';
import Vuex from 'vuex';
import $http from 'axios';
import $ from 'jquery';

Vue.use(Vuex);

export default new Vuex.Store({
    state: {
        LANG: PINOOX.LANG,
        animDirection: (PINOOX.LANG.manager.direction === 'rtl' ? 'Right' : 'Left'),
        time: 0,
        user: {},
        pinoox: {},
        isLoading: false,
        isLoadingUpdate: false,
        isLogin: null,
        isLock: null,
        isRun: false,
        isApp: false,
        manual: {
            xhr: null,
            percent: 0,
            errs:null,
            message:null,
        },
        sidebar: {
            back: false,
            menus: [],
        },
        clock: '',
        storage: '',
        options: {
            background: '',
            lock_time: 0,
            lang: 'fa'
        },
        connectData:{
            connect_key:null,
            token_key:null,
        },
        apps: {},
        isOpenNotification: false,
        notifications: {
            db: [],
            setting: [],
        },
        notifier: {
            isShow: false,
            actions: {},
            title: '',
            message: '',
            type: '',
            isCancelable: false,
            timer: 5,//in seconds
            interval: null,
        },
        pinooxAuth: {isLogin: false},
        tabs: [],
        tabCurrent: {},
        floatInstaller: null,

    },
    setters: {},
    getters: {
        background: state => {
            if (false)
                return PINOOX.URL.THEME + 'dist/images/backgrounds/' + state.options.background + '.svg';
            else
                return PINOOX.URL.THEME + 'dist/images/backgrounds/' + state.options.background + '.jpg';
        },
        isBackground: state => {
            return !!state.options.background;
        },
        isOpenNotification: state => {
            return state.isOpenNotification;
        },
        appsArray: state => {
            return Object.values(state.apps);
        },
        hasNotification: state => {
            let db = state.notifications.db.filter(function (notification) {
                return notification.status === 'send';
            });
            let setting = state.notifications.setting.filter(function (notification) {
                return notification.status === 'send';
            });
            return (setting.length + db.length) > 0;
        },
    },
    mutations: {
        startTimer: (state) => {
            return setInterval(() => {
                state.time++;
            }, 1000);
        },
        running: (state) => {
            if (!state.isRun)
                state.isRun = true;
        },
        logout: (state) => {
            state.isLoading = true;
            $http.get(PINOOX.URL.API + 'user/logout').then((json) => {
                state.isLoading = false;
                if (json.data.status) {
                    state.isLogin = false;
                    state.isLock = false;
                    state.user = {};
                }
            });
        },
        lock: (state) => {
            state.isLoading = true;
            $http.get(PINOOX.URL.API + 'user/lock').then((json) => {
                state.isLoading = false;
                if (json.data.status) {
                    state.isLock = true;
                    state.user = json.data.result;
                }
            });
        },
        toggleNotification: (state) => {
            state.isOpenNotification = !state.isOpenNotification;

            $(document).mouseup(function (e) {
                var notifications = $(".notifications");
                var ntfDrawer = $(".ntf-drawer");

                if (!notifications.is(e.target) && notifications.has(e.target).length === 0 &&
                    !ntfDrawer.is(e.target) && ntfDrawer.has(e.target).length === 0) {
                    state.isOpenNotification = false;
                }
            });
        },
        notify: (state, data) => {

            //reset and hide active notifier
            if (state.notifier.isShow) {
                state.notifier.isShow = false;
                clearInterval(state.notifier.interval);
            }

            var delay = setTimeout(function () {
                state.notifier.isShow = true;
                state.notifier.title = data.title;
                state.notifier.message = data.message;
                state.notifier.type = data.type;
                state.notifier.actions = data.actions;
                clearTimeout(delay);
            }, 100);

            var timer = (data.timer <= 0) ? state.notifier.timer : data.timer;
            state.notifier.interval = setInterval(function () {
                timer--;
                if (timer === 0) {
                    state.notifier.isShow = false;
                    clearInterval(state.notifier.interval);
                }
            }, 1000);
        },
        getApps: (state) => {
            $http.get(PINOOX.URL.API + 'app/get').then((json) => {
                state.apps = json.data;
            });
        },
        updateDirections: (state, direction) => {
            document.body.className = direction;
            state.animDirection = direction === 'rtl' ? 'Right' : 'Left';
        },
        pushToTabs: (state, info) => {
            state.tabCurrent = {
                key: info.key,
                label: !!info.label ? info.label : null,
                icon: !!info.icon ? info.icon : null,
                image: !!info.image ? info.image : null,
                route: {},
            };

            if (info.key === 'home')
                return;
            let result = state.tabs.find(tab => tab.key === state.tabCurrent.key);
            if (result === undefined)
                state.tabs.push(state.tabCurrent);
        },
        closeFromTabs(state, key) {
            state.tabs = state.tabs.filter(function (tab) {
                return tab.key !== key;
            });
        },
        pushToNotifications: (state, data) => {
            data = {
                key: data.key,
                title: !!data.title ? data.title : null,
                message: !!data.message ? data.message : null,
                route: !!data.route ? data.route : null,
                percent: !!data.percent ? data.percent : null,
            };

            let index = state.notifications.setting.findIndex(notification => notification.key === data.key);
            if (index < 0)
            {
                data.status = 'send';
                state.notifications.setting.push(data);
            }
            else
            {
                if(!!state.notifications.setting[index].status)
                    data.status = state.notifications.setting[index].status;
                Vue.set(state.notifications.setting, index, data);
            }
        },
        closeFromNotifications(state, key) {
            state.notifications.setting = state.notifications.setting.filter(function (notification) {
                return notification.key !== key;
            });
        }
    },
    actions: {
        run({commit}) {
            commit('startTimer');
            commit('running');
        },
    }
});