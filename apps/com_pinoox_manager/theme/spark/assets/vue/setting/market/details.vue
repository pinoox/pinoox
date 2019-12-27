<template>
    <div class="app-details">
        <router-link tag="span" :to="{name:'setting-market'}" class="return pin-btn"><i
                class="fa fa-chevron-right"></i> {{LANG.manager.return}}
        </router-link>
        <div v-if="isLoading" class="pin-spinner"></div>
        <div v-else>
            <br>
            <br>
            <div class="header-details">
                <img class="app-icon" :src="app.icon">
                <div class="app-info">
                    <h2>{{app.app_name}}</h2>
                    <h3>{{LANG.manager.developer}}: {{app.developer}}</h3>
                    <h3>{{LANG.manager.version}}: {{app.version_name}}</h3>
                    <h3>{{LANG.manager.require_space}}: {{app.app_size}}</h3>
                </div>
                <div class="action">
                    <div class="btn-install" @click="downloadApp()">{{LANG.manager.download}}</div>
                </div>
            </div>
            <div class="content-details">
                <div class="text" v-html="app.description"></div>
            </div>

        </div>

    </div>
</template>
<script>
    import {mapGetters} from 'vuex';

    export default {
        props: ['package_name'],
        data() {
            return {
                isLoading: false,
                app: {}
            }
        },
        computed: {
            ...mapGetters(['pinooxAuth']),
        },
        methods: {
            getApp() {
                this.isLoading = true;
                this.$http.get(this.URL.API + 'market/getOneApp/' + this.package_name).then((json) => {
                    this.isLoading = false;
                    this.app = json.data;
                });
            },
            downloadApp() {
                if (this.pinooxAuth.isLogin) {
                    this.$http.get(this.URL.API + 'market/downloadRequest/' + this.package_name).then((json) => {
                        this.isLoading = false;
                    });
                } else {
                    this.$router.push({name: 'market-login'});
                }
            }
        },
        created() {
            this.getApp();
        }
    }
</script>