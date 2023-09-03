import Vue from "vue";
import {mapMutations} from 'vuex';

Vue.mixin({
    computed: {
        LANG: {
            get(){
                return this.$store.state.LANG;
            },
            set(val)
            {
                this.$store.state.LANG = val;
            }
        },
        OPTIONS: {
            get(){
                return this.$store.state.OPTIONS;
            },
            set(val)
            {
                this.$store.state.OPTIONS = val;
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
        _redirect(path, seconds) {
            let s = seconds * 1000;
            setTimeout(function () {
                window.location = path;
            }, s);
        },
        _notify(title, message, type = '', actions = null, timer = 5) {
            this.notify({title: title, message: message, type: type, actions: actions, timer: timer});
        }
    }
});
