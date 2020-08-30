<template>
    <div id="pinoox-container">
        <img v-show="isBackground" :src="background" class="cover-background">
        <notifier></notifier>
        <Notifications></Notifications>
        <div id="pin-bar" v-if="isLogin && !isLock && $router.currentRoute.name !== 'loading'">
            <div class="pin-icon ntf-drawer" @click="toggleNotification()">
                <img src="@img/pin-icon.png">
                <span v-if="hasNotification" class="notify"><i class="fa fa-bell  animated bounceIn loop"></i></span>
            </div>
            <div class="appManage-tabs" v-if="appManager!=null && appManager.length>0">
                <div class="tab-app" v-for="(app,index) in appManager">
                    <i @click="closeTab(app)" class="icon fa fa-times"></i>
                    <span @click="openTab(app)" class="app-name">{{app.name}}</span>
                </div>
            </div>
            <router-link :to="{name:'appManager-home'}" class="pin-icon" v-if="notifyInstaller>0">
                <i class="fas fas fa-grip-horizontal fontIcon"></i> <span class="label">{{LANG.manager.ready_to_install}}</span>
                <span class="notify"><i class="fa fa-bell  animated bounceIn loop"></i></span>
            </router-link>
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

    export default {
        components: {Notifications, Notifier},
        data() {
            return {
                timeSleep: 0,
                startRoute: {},
            }
        },
        computed: {
            ...mapState(['isLoading', 'isRun', 'time', 'isApp', 'appManager']),
            ...mapGetters(['background', 'isBackground', 'isOpenNotification', 'hasNotification']),
            notifyInstaller: {
                get() {
                    return this.$store.state.readyInstallCount;
                }
            },
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
            appManager: {
                get() {
                    return this.$store.state.appManager;
                },
                set(val) {
                    this.$store.state.appManager = val;
                }
            },
        },
        methods: {
            ...mapActions(['run']),
            ...mapMutations(['logout', 'lock', 'getApps', 'toggleNotification', 'getLang', 'getReadyToInstallApp', 'getPinooxAuth', 'closeFromAppManager']),
            getOptions() {
                this.$http.get(this.URL.API + 'options/get').then((json) => {
                    this.options = json.data;
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
                        this.notifications = json.data.result;
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
                    this.getNotifications();
                    this.checkVersion();
                    this.getReadyToInstallApp();
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
            openTab(app) {
                if (!!app.open) {
                    this.$router.push({
                        name: app.open
                    }).catch(err => {
                    });
                } else {
                    this.$router.push({
                        name: 'appManager-details',
                        params: {package_name: app.package_name}
                    }).catch(err => {
                    });
                }

            },
            closeTab(app) {
                if (app.package_name === this.$route.params.package_name) {
                    this.$router.replace({
                        name: 'appManager-home'
                    }).catch(err => {
                    });
                }
                this.closeFromAppManager(app);
            }
        },
        created() {
            this.setStartRouter();
            this.$router.replace({name: 'loading'});
            this.getUser();
            this.getOptions();
            this.getPinooxAuth();

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
            }
        }
    };

</script>