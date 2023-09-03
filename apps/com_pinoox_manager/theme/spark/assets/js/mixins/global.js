import Vue from "vue";
import {mapMutations} from 'vuex';
import $http from "axios";

Vue.mixin({
    created() {
        if (!!this.$parent && !!this.$parent.sidebar) {
            this._sidebar = this.$parent.sidebar;
        }

    },
    computed: {
        LANG: {
            set(val) {
                this.$store.state.LANG = val;
            },
            get() {
                return this.$store.state.LANG;
            }
        },
        _connect: {
            get() {
                return this.$store.state.connectData;
            },
            set(val) {
                this.$store.state.connectData = val;
            }
        },
        _sidebar: {
            set(val) {
                this.$store.state.sidebar = val;
            },
            get() {
                let sidebar = this.$store.state.sidebar;
                return !!sidebar ? {
                    enable: sidebar.enable !== undefined ? sidebar.enable : true,
                    title: !!sidebar.title ? sidebar.title : false,
                    back: !!sidebar.back ? sidebar.back : false,
                    menus: !!sidebar.menus ? sidebar.menus : [],
                    topList: !!sidebar.topList ? sidebar.topList : [],
                    app: !!sidebar.app ? sidebar.app : false,
                } : false;
            }
        },
        URL() {
            return PINOOX.URL;
        },
        USER: {
            get() {
                return this.$store.state.user;
            },
            set(val) {
                this.$store.state.user = val;
            }
        },
        isLogin: {
            get() {
                return this.$store.state.isLogin;
            },
            set(val) {
                this.$store.state.isLogin = val;
            }
        },
        tabCurrent: {
            get() {
                return this.$store.state.tabCurrent;
            },
            set(val) {
                this.$store.state.tabCurrent = val;
            }
        },
        _loading: {
            get() {
                return this.$store.state.isLoading;
            },
            set(val) {
                this.$store.state.isLoading = val;
            }
        },
        pinooxAuth: {
            get() {
                return this.$store.state.pinooxAuth;
            },
            set(val) {
                this.$store.state.pinooxAuth = val;
            }
        },
        isLock: {
            get() {
                return this.$store.state.isLock;
            },
            set(val) {
                this.$store.state.isLock = val;
            }
        },
        floatInstaller: {
            get() {
                return this.$store.state.floatInstaller;
            },
            set(val) {
                this.$store.state.floatInstaller = val;
            }
        },
    },
    methods: {
        ...mapMutations(['notify', 'pushToTabs', 'closeFromTabs','pushToNotifications','closeFromNotifications']),
        _isEmptyObj(obj) {
            return Object.keys(obj).length === 0
        },
        logoutPinooxAuth() {
            return this.$http.get(PINOOX.URL.API + 'account/logout').then((json) => {
                this.pinooxAuth = {isLogin: false};
            });

        },
        getPinooxAuth() {
            return this.$http.get(PINOOX.URL.API + 'account/getPinooxAuth').then((json) => {
                this.pinooxAuth = (json.data === null || !json.data) ? {isLogin: false} : json.data;
            });
        },
        _redirect(path, seconds) {
            let s = seconds * 1000;
            setTimeout(function () {
                window.location = path;
            }, s);
        },
        _notify(title, message, type = '', actions = null, timer = 5) {
            this.notify({title: title, message: message, type: type, actions: actions, timer: timer});
        },
        _openFloatInstaller(pack,state,actionSuccessful = null,actionUnsuccessful = null) {

            this.floatInstaller = pack;
            this.floatInstaller['state'] = state; //market or theme or manual
            this.floatInstaller['actionSuccessful'] = actionSuccessful;
            this.floatInstaller['actionUnsuccessful'] = actionUnsuccessful;
        },
        _delay: (function () {
            let timer = 0;
            return function (callback, ms) {
                clearTimeout(timer);
                timer = setTimeout(callback, ms);
            };
        })(),
    }
});
