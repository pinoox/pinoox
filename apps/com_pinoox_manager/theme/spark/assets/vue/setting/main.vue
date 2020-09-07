<template>
    <section>
        <div class="windows-page">
            <div v-if="_sidebar.enable" class="sidebar" data-simplebar>
                <sidebar></sidebar>
            </div>
            <router-view></router-view>
        </div>
    </section>
</template>

<script>
    import Sidebar from '../sidebar.vue';

    export default {
        components: {Sidebar},
        created() {
            this.pushToTabs({
                key: 'setting',
                label: 'setting',
                icon: 'fa fa-cog',
            });
        },
        data() {
            return {
                sidebar: {
                    back: false,
                    menus: [
                        {
                            name: 'setting-dashboard',
                            label: 'interface',
                            icon: 'fas fa-tv',
                            params: {},
                        },
                        {
                            name: 'setting-account',
                            label: 'account',
                            icon: 'fas fa-user',
                            params: {},
                        },
                        {
                            name: 'setting-router',
                            label: 'router',
                            icon: 'fas fa-code-branch',
                            params: {},
                        },
                        {
                            name: 'apps-home',
                            label: 'app_manager',
                            icon: 'fas fa-grip-horizontal',
                            params: {},
                        },
                        {
                            name: 'setting-market',
                            label: 'market',
                            img: require('@img/market-icon.png'),
                            left: {
                                icon: 'fas fa-user-check',
                                isLogin: true,
                            },
                            params: {},
                        },
                        {
                            name: 'setting-about',
                            label: 'about',
                            img: require('@img/pin-icon.png'),
                            params: {},
                        },
                    ],
                }
            }
        },

    }
</script>

