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
        clock: '',
        storage: '',
        options: {
            background: '',
            lock_time: 0,
            lang: 'fa'
        },
        apps: {},
        isOpenNotification: false,
        notifications: [],
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
        readyInstallCount: 0
    },
    getters: {
        background: state => {
            if (state.options.background === 6)
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
            for (let index in state.notifications) {
                let notification = state.notifications[index];
                if (notification.status === 'send')
                    return true;
            }
            return false;
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
                let notifications = $(".notifications");
                let ntfDrawer = $(".ntf-drawer");

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
        logoutPinooxAuth: (state) => {
            $http.get(PINOOX.URL.API + 'account/logout').then((json) => {
                state.pinooxAuth = {isLogin: false};
            });

        },
        getReadyToInstallApp: (state) => {
            $http.get(PINOOX.URL.API + 'app/readyInstallCount').then((json) => {
                state.readyInstallCount = json.data;
            });
        },
        getPinooxAuth: (state) => {
            $http.get(PINOOX.URL.API + 'account/getPinooxAuth').then((json) => {
                state.pinooxAuth = (json.data === null || !json.data) ? {isLogin: false} : json.data;
            });
        },
    },
    actions: {
        run({commit}) {
            commit('startTimer');
            commit('running');
        }
    }
});