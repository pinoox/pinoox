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
                    <div v-if="state==='download'" class="btn-install" @click="downloadApp()">
                        {{LANG.manager.download}}
                    </div>
                    <div v-if="state==='downloading'" class="btn-install pin-loader"><i class="fa fa-spinner"></i>
                        {{LANG.manager.downloading}}
                    </div>
                    <span class="warn-text" v-if="state==='downloading'">{{LANG.setting.market.please_wait_until_download_complete}}</span>
                    <div v-if="state==='install'" class="btn-install" @click="installApp()">{{LANG.manager.install}}
                    </div>
                    <div v-if="state==='install'" class="btn-install" @click="downloadApp()">
                        {{LANG.manager.re_download}}
                    </div>

                    <div v-if="state==='installing'" class="btn-install">
                        {{LANG.setting.market.installing}}
                    </div>
                    <span class="warn-text" v-if="state==='installing'">{{LANG.setting.market.please_wait_until_install_complete}}</span>


                    <div v-if="state==='installed'" class="btn-install" @click="removeApp()">
                        {{LANG.manager.delete}}
                    </div>
                </div>
            </div>
            <div class="content-details">
                <div class="text" v-html="app.description"></div>
            </div>

        </div>
    </div>
</template>
<script>
    import {mapGetters, mapMutations} from 'vuex';

    export default {
        props: ['package_name'],
        data() {
            return {
                isLoading: false,
                state: 'download',
                app: {}
            }
        },
        computed: {
            pinooxAuth: {
                get() {
                    return this.$store.state.pinooxAuth;
                }
            },
            readyInstall: {
                set(val) {
                    this.$store.state.readyInstallCount = val;
                },
                get() {
                    return this.$store.state.readyInstallCount;
                }
            }
        },
        methods: {
            ...mapMutations(['logoutPinooxAuth']),
            getApp() {
                this.isLoading = true;
                this.$http.get(this.URL.API + 'market/getOneApp/' + this.package_name).then((json) => {
                    this.isLoading = false;
                    this.app = json.data;
                    this.state = this.app.state;
                });
            },
            downloadApp() {
                if (this.pinooxAuth.isLogin) {
                    this.state = 'downloading';
                    this.$http.post(this.URL.API + 'market/downloadRequest/' + this.package_name, {auth: this.pinooxAuth}).then((json) => {
                        if (!json.data.status) {
                            this.state = 'download';
                            this._notify(this.LANG.user.login_to_pinoox, json.data.result.message, 'warning');
                            if (json.data.result.require_auth) {
                                this.logoutPinooxAuth();
                                this.$router.push({name: 'market-login'});
                            }
                        } else {
                            this.state = 'install';
                            this.readyInstall++;
                            this._notify(this.LANG.manager.success, json.data.result, 'success');
                        }
                    });

                } else {
                    this.$router.push({name: 'market-login'});
                }
            },
            installApp() {
                this.state = 'installing';
                this.$http.get(this.URL.API + 'app/install/' + this.app.package_name).then((json) => {
                    if (json.data.status) {
                        this._notify(this.LANG.manager.installed_successfully, '', 'success');
                        this.state = 'installed';
                    } else {
                        this.state = 'install';
                    }
                });
            },
            updateApp() {
                this.isLoadingUpdate = true;
                this.$http.post(this.URL.API + 'app/update/', {
                    packageName: this.selectedApp.package_name,
                    downloadLink: this.selectedApp.download_link,
                    versionName: this.selectedApp.version_name,
                    versionCode: this.selectedApp.version_code,
                }).then((json) => {
                    this.isLoadingUpdate = false;
                    if (json.data.status) {
                        this._notify(this.LANG.manager.update_app, json.data.result, 'success');
                        this.app.version = this.selectedApp.version_name;
                        this.app.version_code = this.selectedApp.version_code;
                    } else {
                        this._notify(this.LANG.manager.update_app, json.data.result, 'danger');
                    }
                });
            },
            removeApp() {
                this._notify(this.LANG.manager.alert, this.LANG.manager.are_you_sure_delete_app, null, [
                    {
                        text: this.LANG.manager.do_delete,
                        func: () => {
                            this._loading = true;
                            this.$http.post(this.URL.API + 'app/remove/' + this.app.package_name).then((json) => {
                                this._loading = false;
                                this.state = this.app.state = 'download';
                                this.$delete(this.$store.state.apps, this.app.package_name);
                            });
                        }
                    },
                    {
                        text: this.LANG.manager.no,
                        func: () => {
                        }
                    }
                ]);
            },
        },
        created() {
            this.getApp();
        }
    }
</script>