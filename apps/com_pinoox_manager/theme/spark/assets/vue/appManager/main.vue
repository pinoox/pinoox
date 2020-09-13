<template>
    <section>
        <div class="windows-page">
            <div v-if="_sidebar.enable" class="sidebar" data-simplebar>
                <sidebar :app="app"></sidebar>
            </div>
            <router-view></router-view>
        </div>
    </section>
</template>

<script>
    import Sidebar from '../sidebar.vue';

    export default {
        components: {Sidebar},
        props: ['package_name'],
        data() {
            return {

            }
        },
        computed: {
            sidebar(){
                return  {
                    enable: true,
                    back: false,
                    app: this.package_name,
                    menus: [
                        {
                            name: 'app-details',
                            label: 'app_details',
                            icon: 'fas fa-file',
                            params: {package_name: this.package_name}
                        },
                        {
                            name: 'app-config',
                            label: 'configs',
                            icon: 'fas fa-cog',
                            params: {package_name: this.package_name}
                        },
                        {
                            name: 'app-templates',
                            label: 'templates',
                            icon: 'fas fa-paint-brush',
                            params: {package_name: this.package_name}
                        },
                    ],
                    topList: [
                        {
                            name: 'apps-home',
                            title: 'app_manager',
                            icon: 'fas fa-boxes',
                            params: {package_name: this.package_name}
                        },
                        {
                            name: 'setting-router',
                            title: 'router',
                            icon: 'fas fa-code-branch',
                        },
                        {
                            name: 'app-view',
                            title: 'preview',
                            icon: 'fa fa-eye',
                            params: {package_name: this.package_name}
                        },
                    ],
                };
            },
            pinooxAuth: {
                get() {
                    return this.$store.state.pinooxAuth;
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
            app: {
                get() {
                    return this.apps[this.package_name];
                },
                set(val) {
                    this.apps[this.package_name] = val;
                }
            },
        },
        watch: {
            'package_name': {
                handler() {
                    this._sidebar = this.sidebar;

                    let app = this.apps[this.package_name];
                    this.pushToTabs({
                        key: 'app-setting:' + this.package_name,
                        label: app.name,
                        icon: 'fa fa-cog',
                    });
                },
                immediate: true,
            }
        }
    }
</script>

