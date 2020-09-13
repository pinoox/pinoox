<template>
    <div class="app-details">
        <router-link tag="span" :to="{name:'market-home'}" class="return pin-btn"><i
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
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <span class="nav-link"
                              @click="changeTab('description')"
                              :class="{active:activeTab==='description'}">{{LANG.manager.description}}</span>
                    </li>
                    <li class="nav-item" v-if="templates!=null && templates.length>0">
                        <span class="nav-link"
                              @click="changeTab('templates')"
                              :class="{active:activeTab==='templates'}">{{LANG.manager.templates}}</span>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active">
                        <div v-if="activeTab==='description'">
                            <div class="text" v-html="app.description"></div>
                        </div>
                        <div v-else-if="activeTab==='templates'">

                            <div class="templates" v-if="templates!=null">
                                <div class="item" v-for="(t,index) in templates">
                                    <img class="thumb" :src="t.cover">
                                    <div class="name">{{t.template_name}}</div>
                                    <div class="actions" v-if="state==='installed'">
                                        <div v-if="t.state==='download'"
                                             @click="downloadTemplate(t)"
                                             class="btn-pin btn-success">
                                            {{LANG.manager.download}}
                                        </div>
                                        <div v-else-if="t.state==='install'"
                                             @click="installTemplate(t)"
                                             class="btn-pin btn-success">
                                            {{LANG.manager.install}}
                                        </div>
                                        <a :href="t.live_preview" v-if="t.live_preview!=null" class="btn-pin">{{LANG.manager.live_preview}}</a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</template>
<script>
    import {mapMutations, mapState} from 'vuex';

    export default {
        props: ['package_name'],
        data() {
            return {
                isLoading: false,
                state: 'download',
                app: {},
                activeTab: 'description',
                templates: null,
            }
        },
        computed: {
            pinooxAuth: {
                get() {
                    return this.$store.state.pinooxAuth;
                }
            },
            installedApps: {
                get() {
                    return this.$store.state.apps;
                }
            },
        },
        methods: {
            ...mapMutations(['getApps', 'logoutPinooxAuth']),
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
                    this._loading = true;
                    this.$http.post(this.URL.API + 'market/downloadRequest/' + this.package_name, {auth: this.pinooxAuth}).then((json) => {
                        this._loading = false;
                        if (!json.data.status) {
                            this.state = 'download';
                            this._notify(this.LANG.user.login_to_pinoox, json.data.result.message, 'warning');
                            if (json.data.result.require_auth) {
                                this.logoutPinooxAuth();
                                this.$router.push({name: 'market-login'});
                            }
                        } else {
                            this.state = 'install';
                            this._notify(this.LANG.manager.success, json.data.result, 'success');
                            this._openFloatInstaller(this.app,'market');
                        }
                    });

                } else {
                    this.$router.push({name: 'market-login'});
                }
            },
            installApp() {
                this._openFloatInstaller(this.app,'market');
            },
            updateApp() {
                this.isLoadingUpdate = true;
                this._loading = true;
                this.$http.post(this.URL.API + 'app/update/', {
                    packageName: this.selectedApp.package_name,
                    downloadLink: this.selectedApp.download_link,
                    versionName: this.selectedApp.version_name,
                    versionCode: this.selectedApp.version_code,
                }).then((json) => {
                    this._loading = false;
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
            changeTab(tab) {
                this.activeTab = tab;
            },
            downloadTemplate(template) {
                if (this.pinooxAuth.isLogin) {
                    this._loading = true;
                    this.$http.post(this.URL.API + 'market/downloadRequestTemplate/' + template.uid, {
                        package_name: this.package_name,
                        auth: this.pinooxAuth
                    }).then((json) => {
                        this._loading = false;
                        if (!json.data.status) {
                            this._notify(this.LANG.user.login_to_pinoox, json.data.result.message, 'warning');
                            if (json.data.result.require_auth) {
                                this.logoutPinooxAuth();
                                this.$router.push({name: 'market-login'});
                            }
                        } else {
                            template.state = 'install';
                            this._notify(this.LANG.manager.success, json.data.result, 'success');
                            this._openFloatInstaller(template,'theme');
                        }
                    });

                } else {
                    this.$router.push({name: 'market-login'});
                }
            },
            getTemplates() {
                this.isLoading = true;
                this.$http.get(this.URL.API + 'market/getTemplates/' + this.package_name).then((json) => {
                    this.isLoading = false;
                    this.templates = json.data;
                });
            },
            installTemplate(template) {
                this._openFloatInstaller(template,'theme');
            }
        },
        created() {
            this.getApp();
            this.getTemplates();
        },
        watch: {
            installedApps(newApps, oldApps) {
                this.state = !!newApps[this.package_name] ? 'installed' : this.state;
            }
        }
    }
</script>