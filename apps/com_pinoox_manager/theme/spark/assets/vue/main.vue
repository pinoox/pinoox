<template>
    <div id="pinoox-container">
        <img v-show="isBackground" :src="background" class="cover-background" alt="">
        <notifier></notifier>
        <Notifications></Notifications>
        <FloatInstaller v-if="floatInstaller!=null" :app="floatInstaller" @close="floatInstaller=null"></FloatInstaller>
        <div id="pin-bar" v-if="isLogin && !isLock && $router.currentRoute.name !== 'loading'">
            <div class="pin-icon ntf-drawer" @click="toggleNotification()">
                <img src="@img/pin-icon.png">
                <span v-if="hasNotification" class="notify"><i class="fa fa-bell  animated bounceIn loop"></i></span>
            </div>
            <div class="appManage-tabs" v-if="tabs!=null && tabs.length>0">
                <div :class="!!tabCurrent.key && tabCurrent.key === 'home'? 'active' : ''" class="tab-app">
                    <router-link tag="span" :to="{name:'home'}" class="tab-details home"><i class="fa fa-home"></i>
                    </router-link>
                </div>
                <div :class="!!tabCurrent.key && tabCurrent.key === tab.key? 'active' : ''" class="tab-app"
                     v-for="(tab,index) in tabs">
                    <i @click="closeTab(tab.key)" class="icon fa fa-times"></i>
                    <router-link tag="div" :to="tab.route" class="tab-details tab-icon">
                        <span v-if="!!tab.label" class="app-name"> {{tab.label}}</span>
                        <i v-if="!!tab.icon" :class="tab.icon"></i>
                        <img v-else-if="!!tab.image" :src="tab.image">
                    </router-link>
                </div>
            </div>
        </div>

        <router-view></router-view>

        <div class="content-loading" v-if="isLoading">
            <div class="lds-roller">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    </div>
</template>

<script>
    import {mapActions, mapGetters, mapMutations, mapState} from 'vuex';
    import Notifications from "./notifications.vue";
    import Notifier from "./notifier.vue";
    import FloatInstaller from "./pages/float-installer.vue";

    export default {
        components: {Notifications, Notifier, FloatInstaller},
        data() {
            return {
                timeSleep: 0,
                startRoute: {},
            }
        },
        computed: {
            ...mapState(['isLoading', 'isRun', 'time', 'isApp']),
            ...mapGetters(['background', 'isBackground', 'isOpenNotification', 'hasNotification']),
            options: {
                get() {
                    return this.$store.state.options;
                },
                set(val) {
                    this.$store.state.options = val;
                }
            },
            apps: {
                get() {
                    return this.$store.state.apps;
                },
                set(val) {
                    this.$store.state.apps = val;
                }
            },
            pinoox: {
                get() {
                    return this.$store.state.pinoox;
                },
                set(val) {
                    this.$store.state.pinoox = val;
                }
            },
            notifications: {
                get() {
                    return this.$store.state.notifications;
                },
                set(val) {
                    this.$store.state.notifications = val;
                }
            },
            tabs: {
                get() {
                    return this.$store.state.tabs;
                },
                set(val) {
                    this.$store.state.tabs = val;
                }
            },
            floatInstaller: {
                get() {
                    return this.$store.state.floatInstaller;
                },
                set(app) {
                    this.$store.state.floatInstaller = app;
                }
            }
        },
        methods: {
            ...mapActions(['run']),
            ...mapMutations(['logout', 'lock', 'getApps', 'toggleNotification', 'closeFromTabs']),
            getOptions() {
                this.$http.get(this.URL.API + 'user/getOptions').then((json) => {
                    this.options = json.data;
                });
            },
          getConnectData() {
                this.$http.get(this.URL.API + 'account/getConnectData').then((json) => {
                    this._connect = json.data;
                });
            },
            getUser() {
                this.$http.get(this.URL.API + 'user/get').then((json) => {
                    if (json.data.status) {
                        this.isLogin = true;
                        if (json.data.result.isLock)
                            this.isLock = true;
                        else
                            this.isLock = false;
                        this.USER = json.data.result;
                    } else {
                        this.isLogin = false;
                        this.USER = {};
                    }
                    this.checkRouterByUser();
                });
            },
            getNotifications() {
                this.$http.get(this.URL.API + 'notification/').then((json) => {
                    if (json.data.status) {
                        this.notifications.db = json.data.result;
                    }
                });
            },
            checkVersion() {
                this.$http.get(this.URL.API + 'update/checkVersion/').then((json) => {
                    this.pinoox = json.data;
                });
            },
            locker() {
                document.onmousemove = () => {
                    this.timeSleep = 0;
                };

                document.onkeypress = () => {
                    this.timeSleep = 0;
                };
            },
            checkRouterByUser() {
                if (this.isLogin && !this.isLock) {
                    this.locker();
                    this.getApps();
                  this.getPinooxAuth();
                  this.getConnectData();
                    this.getNotifications();
                    this.checkVersion();
                } else {
                    this.$store.state.apps = {};
                }
                if (this.$router.currentRoute.name === 'loading') {
                    setTimeout(() => {
                        this.userAccess();
                    }, 1000);
                } else {
                    this.userAccess();
                }
            },
            userAccess() {
                if (this.isLogin && !this.isLock) {
                    let r = this.startRoute;
                    this.$router.replace({name: r.name, params: r.params});
                    //this.$router.replace({name: 'home'});
                } else {
                    this.$router.replace({name: 'login'});
                }
            },
            setStartRouter() {
                if (this.$router.currentRoute.name !== null && this.$router.currentRoute.name !== 'loading' && this.$router.currentRoute.name !== 'login')
                    this.startRoute = this.$router.currentRoute;
                else
                    this.startRoute = {name: 'home'};
            },
            closeTab(key) {
                this.closeFromTabs(key);
                let length = this.tabs.length;
                if (length > 0) {
                    if (!!this.tabCurrent.key && key === this.tabCurrent.key) {
                        length--;
                        let tab = this.tabs[length];
                        this.$router.push(tab.route);
                    }
                } else {
                    this.$router.push({name: 'home'});
                }
            },
            updateRouteTabs(route) {
                this.$nextTick(() => {
                    if (!this.tabCurrent.key || this.tabCurrent.key === 'home')
                        return;
                    let tab = this.tabs.find(t => t.key === this.tabCurrent.key);
                    tab.route = {
                        name: route.name,
                        params: route.params,
                        query: route.query,
                    };
                });
            }
        },
        created() {
            this.setStartRouter();
            this.$router.replace({name: 'loading'});
            this.getUser();
            this.getOptions();

        },
        mounted() {
            this.run();
        },
        watch: {
            'isLogin': function (val, oldVal) {
                if (oldVal !== null)
                    this.checkRouterByUser();
            },
            'isLock': function (val, oldVal) {
                if (oldVal !== null)
                    this.checkRouterByUser();
            },
            'time': function () {
                if (!this.isLogin || this.isLock) return;
                this.timeSleep++;
                let timeEnd = this.options.lock_time * 60;
                let lockAvailable = (this.options.lock_time > 0);
                if (lockAvailable && this.timeSleep >= timeEnd) {
                    this.lock();
                }
            },
            '$route': {
                handler(route) {
                    this.updateRouteTabs(route);
                },
                immediate: true,
            },
        }
    };

</script>