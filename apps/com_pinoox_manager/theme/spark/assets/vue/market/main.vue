<template>
    <section>
        <div class="windows-page">
            <div v-if="_sidebar.enable" class="sidebar" data-simplebar>
                <sidebar></sidebar>
            </div>
            <div class="content">
                <div class="header">
                    <div class="text">
                        <h1>{{LANG.manager.market}}</h1>
                        <h2></h2>
                    </div>
                </div>
                <div class="page" data-simplebar data-simplebar-auto-hide="false">
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
                    title: this.LANG.setting.market.pinoox_market_title,
                    back: false,
                    menus: [
                        {
                            name: 'market-home',
                            label: 'market_home',
                            img: require('@img/market-icon.png'),
                        },
                        {
                            name: 'market-downloads',
                            label: 'recent_downloads',
                            icon: 'fas fa-file-download',
                        },
                        {
                            name: 'market-account',
                            label: 'market_account',
                            icon: 'fas fa-user',
                            left: {
                                icon: 'fas fa-user-check',
                                isLogin: true,
                            },
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

