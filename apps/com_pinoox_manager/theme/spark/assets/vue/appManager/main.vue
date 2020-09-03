<template>
    <section>
        <div class="windows-page">
            <div class="sidebar" data-simplebar :key="forceRenderKey">
                <div>
                <router-link tag="div" v-if="isMainMenu" :to="{name: 'setting-dashboard'}" class="item back">
                    <i class="fas fa-chevron-right"></i>&nbsp;
                    <span class="name"> {{LANG.manager.back}}</span>
                </router-link>

                    <div class="app-info"  v-if="selectedApp!=null">
                        <img :src="selectedApp.icon" class="icon">
                        <span class="name">{{selectedApp.name}}</span>
                    </div>
                </div>
                <router-link replace v-for="menu in menus" exact-active-class="active" class="item"
                             :to="{name:menu.name,params:menu.params}">
                    <img v-if="menu.img!=null" :src="menu.img">
                    <i v-else class="fas" :class="menu.icon"></i>
                    &nbsp;<span class="name">{{LANG.manager[menu.label]}}</span>
                </router-link>
            </div>
            <router-view @onUpdatePackageName="updatePackageName"></router-view>
        </div>
    </section>
</template>

<script>

    export default {
        data() {
            return {
                forceRenderKey: 0,
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
                            name: 'appManager-details',
                            label: 'app_details',
                            icon: 'fas fa-file',
                            params: {package_name: this.packageName}
                        },
                        {
                            name: 'appManager-config',
                            label: 'configs',
                            icon: 'fas fa-cog',
                            params: {package_name: this.packageName}
                        },
                        {
                            name: 'appManager-users',
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
                            name: 'appManager-home',
                            label: 'apps_list',
                            icon: 'fas fa-grip-horizontal',
                        },

                        {
                            name: 'appManager-manual',
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
                this.menus = this.isMainMenu ? this.mainMenus : this.appMenus;
                if (this.isMainMenu) {
                    this.pushToTabs({
                        key:'setting',
                        label:'setting',
                        icon: 'fa fa-cog',
                    });
                    this.selectedApp = null;
                }
                else if(!!this.packageName)
                {
                    let app = this.apps[this.packageName];
                    this.pushToTabs({
                        key:'setting:'+this.packageName,
                        label:app.name,
                        icon: 'fa fa-cog',
                    });
                }
            },
            updatePackageName(val) {
                this.forceRenderKey++;
                this.packageName = val;
                this.selectedApp = this.apps[val];
                this.switchMenus();
            },
        },
        watch: {
            $route: {
                handler(){
                    this.switchMenus();
                },
                immediate:true,
            },
        }

    }
</script>

