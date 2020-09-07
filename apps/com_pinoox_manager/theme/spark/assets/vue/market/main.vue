<template>
    <section>
        <div class="windows-page">
            <div v-if="_sidebar.enable" class="sidebar" data-simplebar>
                <sidebar></sidebar>
            </div>
            <div class="content" data-simplebar>
                <div class="header">
                    <div class="text">
                        <h1>{{LANG.manager.market}}</h1>
                        <h2></h2>
                    </div>
                </div>
                <div class="page">
                    <div class="market">
                        <router-view></router-view>
                    </div>
                </div>
            </div>

        </div>
    </section>
</template>

<script>
    import Sidebar from '../sidebar.vue';
    import {mapState} from 'vuex';

    export default {
        components: {Sidebar},
        computed: {
            ...mapState(['apps']),
            sidebar() {
                return {
                    enable: true,
                    back: false,
                    menus: [
                        {
                            name: 'market-home',
                            label: 'market_home',
                            img: require('@img/market-icon.png'),
                            left: {
                                icon: 'fas fa-user-check',
                                isLogin: true,
                            },
                        },
                        {
                            name: 'market-account',
                            label: 'market_account',
                            icon: 'fas fa-user',
                        },
                    ],
                };
            },
            appIcon() {
                return this.apps['com_pinoox_market'].icon;
            }
        },
        created() {
            this.pushToTabs({
                key: 'market',
                label: 'market',
                image: this.appIcon,
            });
        },

    }
</script>

