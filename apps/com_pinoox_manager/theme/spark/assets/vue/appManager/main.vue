<template>
    <section>
        <div class="windows-page">
            <div class="sidebar" data-simplebar>
                <div>
                    <router-link tag="div" v-if="isMainMenu" :to="{name: 'setting-dashboard'}" class="item back">
                        <i class="fas fa-chevron-right"></i>&nbsp;
                        <span class="name"> {{LANG.manager.back_to_setting}}</span>
                    </router-link>

                    <div class="app-info" v-if="!!this.packageName">
                        <img :src="selectedApp.icon" class="icon">
                        <span class="name">{{selectedApp.name}}</span>
                        <div class="text">
                            <router-link :to="{name:'app-home',params:{package_name:selectedApp.package_name}}"
                                         class="btn" v-bind:title="LANG.manager.app_manager"><i
                                    class="fas fa-boxes"></i></router-link>
                            <router-link v-if="selectedApp.router" tag="span" :to="{name:'setting-router'}" class="btn"
                                         v-bind:title="LANG.manager.router"><i class="fas fa-code-branch"></i>
                            </router-link>
                            <router-link :to="{name:'app-view',params:{package_name:selectedApp.package_name}}"
                                         class="btn" :title="LANG.manager.preview"><i
                                    class="fa fa-eye"></i></router-link>
                        </div>
                    </div>
                </div>
                <router-link replace v-for="menu in menus" exact-active-class="active" class="item"
                             :to="{name:menu.name,params:menu.params}">
                    <img v-if="menu.img!=null" :src="menu.img">
                    <i v-else class="fas" :class="menu.icon"></i>
                    &nbsp;<span class="name">{{LANG.manager[menu.label]}}</span>
                </router-link>
            </div>
            <router-view></router-view>
        </div>
    </section>
</template>

<script>

    export default {
        data() {
            return {
                packageName: '',
                menusList: [],
                selectedApp: null,
            }
        },
        computed: {
            pinooxAuth: {
                get() {
                    return this.$store.state.pinooxAuth;
                }
            },
            notifyInstaller: {
                get() {
                    return this.$store.state.readyInstallCount;
                }
            },
            menus: {
                get() {
                    return this.menusList;
                },
                set(val) {
                    this.menusList = val;
                }
            },
            appMenus: {
                get() {
                    return [
                        {
                            name: 'app-details',
                            label: 'app_details',
                            icon: 'fas fa-file',
                            params: {package_name: this.packageName}
                        },
                        {
                            name: 'app-config',
                            label: 'configs',
                            icon: 'fas fa-cog',
                            params: {package_name: this.packageName}
                        },
                        {
                            name: 'app-users',
                            label: 'users',
                            icon: 'fas fa-users',
                            params: {package_name: this.packageName}
                        },
                    ];
                }
            },
            mainMenus: {
                get() {
                    return [
                        {
                            name: 'app-home',
                            label: 'apps_list',
                            icon: 'fas fa-grip-horizontal',
                        },

                        {
                            name: 'app-manual',
                            label: 'manual_installation',
                            icon: 'fas fa-archive',
                        },

                    ];
                }
            },
            isMainMenu: {
                get() {
                    return !!this.$route.meta.showMainMenu;
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
        },
        methods: {
            switchMenus() {
                this.packageName = (!!this.$route.params.package_name)? this.$route.params.package_name : null;
                this.menus = !this.packageName ? this.mainMenus : this.appMenus;

                if (!this.packageName) {
                    this.pushToTabs({
                        key: 'setting',
                        label: 'setting',
                        icon: 'fa fa-cog',
                    });
                    this.selectedApp = null;
                } else {
                    this.selectedApp = this.apps[this.packageName];
                    this.pushToTabs({
                        key: 'app-setting:' + this.packageName,
                        label: this.selectedApp.name,
                        icon: 'fa fa-cog',
                    });
                }
            },
        },
        watch: {
            $route: {
                handler() {
                    this.switchMenus();
                },
                immediate: true,
            },
        }

    }
</script>

