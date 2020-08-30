<template>
    <div class="content" data-simplebar>
        <div class="header" v-if="$parent.selectedApp!=null">
            <div class="text">
                <h1>{{LANG.manager.apps_list}}</h1>
            </div>
        </div>
        <div class="page">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <span class="nav-link"
                          @click="loadApps('installed')"
                          :class="activeTab === 'installed'? 'active' : ''">{{LANG.manager.installed_apps}}</span>
                </li>
                <li class="nav-item">
                    <span class="nav-link"
                          @click="loadApps('systems')"
                          :class="activeTab === 'systems'? 'active' : ''">{{LANG.manager.systems_apps}}</span>
                </li>
                <li class="nav-item" v-if="installCount>0">
                    <span class="nav-link"
                          @click="loadApps('ready_install')"
                          :class="activeTab === 'ready_install'? 'active' : ''">{{LANG.manager.ready_to_install}} ({{installCount}})</span>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active">
                    <div class="apps" v-if="!_isEmptyObj(apps) && !isLoading">
                        <div @click="showDetailsApp(app)" class="app-item" v-for="(app,index) in apps">
                            <div class="icon">
                                <img :src="app.icon" :alt="app.name">
                                <div class="text">
                                    <h2 class="name">{{app.name}}</h2>
                                    <h3 class="info">{{LANG.manager.developer}}: {{app.developer}}</h3>
                                    <h3 class="info">{{LANG.manager.version}}: {{app.version}}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pin-spinner" v-else-if="isLoading">
                    </div>
                    <div class="empty" v-else>
                        <div>{{LANG.setting.appManager.empty_app}}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import {mapMutations} from 'vuex';

    export default {
        data() {
            return {
                isLoading: false,
                activeTab: 'installed',
                apps: []
            }
        },
        computed: {
            installCount: {
                get() {
                    return this.$store.state.readyInstallCount;
                },
                set(val) {
                    this.$store.state.readyInstallCount = val;
                }
            },
        },
        methods: {
            ...mapMutations(['getApps', 'pushToAppManager']),
            loadApps(activeTab) {
                this.isLoading = true;
                if (activeTab != null)
                    this.activeTab = activeTab;
                this.$http.get(this.URL.API + 'app/get/' + this.activeTab).then((json) => {
                    this.isLoading = false;
                    this.apps = json.data
                });
            },
            showDetailsApp(app) {
                this.$parent.selectedApp = app;
                this.pushToAppManager(app);
                this.$router.push({name: 'appManager-details', params: {package_name: app.package_name}});
            }

        },
        created() {
            this.loadApps();
        }
    }
</script>
