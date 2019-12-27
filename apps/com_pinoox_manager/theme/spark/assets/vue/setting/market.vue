<template>
    <div class="content" ref="content">
        <div class="header">
            <h1>{{LANG.setting.market.pinoox_market_title}}</h1>
            <h2>{{LANG.setting.market.market_help }}</h2>
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
                installList: state => state.installList,
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
        },
        methods: {
            ...mapMutations(['getApps', 'addToInstallList']),
            search() {
                this.isLoading = true;
                this._delay(() => {
                    this.$http.get(this.URL.API + 'app/market/' + this.keyword).then((json) => {
                        this.isLoading = false;
                        this.apps = json.data.items;
                        for (let i = 0; i < this.apps.length; i++) {
                            this.apps[i]['install_state'] = 'download';

                            //update base on installed apps
                            for (let j = 0; j < this.appsArray.length; j++) {
                                if (this.apps[i].package_name === this.appsArray[j].package_name) {
                                    this.apps[i]['install_state'] = 'installed';
                                    break;
                                }
                            }

                            //update state based on installing list
                            for (let k = 0; k < this.installList.length; k++) {
                                if (this.apps[i].package_name === this.installList[k].package_name) {
                                    this.apps[i]['install_state'] = 'installing';
                                    break;
                                }
                            }
                        }
                    });
                }, 250);

            },
            openAppDetails(index) {
                this.state = 'details';
                this.index = index;
            },
            gotoHomeMarket() {
                this.state = 'home';
            },
            downloadApp(package_name, download_link) {
                this.$http.post(this.URL.API + 'app/download/', {
                    packageName: package_name,
                    downloadLink: download_link,
                }).then((json) => {
                    if (json.data.status) {
                        this.installApp(package_name);
                    } else {
                        this.selectedApp.install_state = 'download';
                        this.finishMessage = this.LANG.setting.market.occurred_error;
                        this._notify(this.LANG.setting.market.installation_failed, this.finishMessage, 'danger');
                    }
                });
            },
            installApp(package_name) {
                this.$http.post(this.URL.API + 'app/install/', {
                    packageName: package_name,
                }).then((json) => {
                    this.state = 'finish';
                    this.finishMessage = json.data.result;
                    this.installList.splice(this.selectedApp, 1);
                    if (json.data.status) {
                        this.selectedApp.install_state = 'installed';
                        this.getApps();
                        this._notify(this.LANG.manager.successful, this.finishMessage, 'success', null, 10);
                    } else {
                        this.selectedApp.install_state = 'download';
                        this.finishMessage = this.LANG.setting.market.occurred_error;
                        this._notify(this.LANG.setting.market.installation_failed, this.finishMessage, 'danger');
                    }
                });
            },
            install() {
                this.state = 'downloading';
                this.selectedApp.install_state = 'installing';
                this.addToInstallList(this.selectedApp);
                this.downloadApp(this.selectedApp.package_name, this.selectedApp.download_link);
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
                        text: this.LANG.manager.delete,
                        func: () => {
                            this._loading = true;
                            this.$http.post(this.URL.API + 'app/remove/', {
                                packageName: this.selectedApp.package_name,
                            }).then((json) => {
                                this._loading = false;
                                this.selectedApp.install_state = 'download';
                                this.$delete(this.$store.state.apps, this.selectedApp.package_name);
                                this.gotoHomeMarket();
                            });
                        }
                    },
                    {
                        text: this.LANG.manager.cancel,
                        func: () => {
                        }
                    }
                ]);
            },
        },
        created() {
            //this.search();
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