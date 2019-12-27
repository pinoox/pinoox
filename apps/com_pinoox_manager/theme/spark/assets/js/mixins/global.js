import Vue from "vue";
import {mapMutations} from 'vuex';

Vue.mixin({
    computed: {
        LANG: {
            set(val) {
                this.$store.state.LANG = val;
            },
            get() {
                return this.$store.state.LANG;
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
        _loading: {
            get() {
                return this.$store.state.isLoading;
            },
            set(val) {
                this.$store.state.isLoading = val;
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
    },
    methods: {
        ...mapMutations(['notify']),
        _isEmptyObj(obj) {
            return Object.keys(obj).length === 0
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
        _delay: (function () {
            let timer = 0;
            return function (callback, ms) {
                clearTimeout(timer);
                timer = setTimeout(callback, ms);
            };
        })(),
    }
});
