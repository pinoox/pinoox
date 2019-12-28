<template>
    <div class="content" ref="content">
        <div class="header">
            <div class="text">
                <h1>{{LANG.setting.market.pinoox_market_title}}</h1>
                <h2>{{LANG.setting.market.market_help }}</h2>
            </div>
            <div class="action" v-if="$route.name!=='market-login'">
                <router-link :to="{name: 'market-account'}" v-if="pinooxAuth.isLogin" class="btn-header">
                    {{LANG.user.account}}
                </router-link>
                <router-link v-else :to="{name: 'market-login'}" class="btn-header">{{LANG.user.login_account}}
                </router-link>
            </div>
        </div>
        <div class="page" data-simplebar data-simplebar-auto-hide="false">
            <div class="market">
                <router-view></router-view>
            </div>
        </div>
    </div>
</template>


<script>
    import {mapGetters, mapMutations, mapState} from 'vuex';

    export default {
        data() {
            return {
                isLoading: false,
                state: 'login',
                finishMessage: '',
                keyword: '',
                index: null,
                isLoadingUpdate: false,
                apps: [],
            }
        },
        computed: {
            ...mapState({
                background: state => state.options.background,
            }),
            ...mapGetters(['appsArray']),
            selectedApp: {
                set(val) {
                    this.apps[this.index] = val;
                },
                get() {
                    return this.apps[this.index];
                }
            },
            app: {
                set(val) {
                    this.$store.state.apps[this.selectedApp.package_name] = val;
                },
                get() {
                    return this.$store.state.apps[this.selectedApp.package_name];
                }
            },
            pinooxAuth: {
                set(val) {
                    this.$store.state.pinooxAuth = val;
                },
                get() {
                    return this.$store.state.pinooxAuth;
                }
            },
        },
        methods: {
            ...mapMutations(['getApps']),
        },
    }
</script>

<style>
    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .pin-loader .fa-spinner {
        -webkit-animation: spin 4s linear infinite;
        -moz-animation: spin 4s linear infinite;
        animation: spin 4s linear infinite;
    }
</style>