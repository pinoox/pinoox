<template>
    <div>
        <ul class="nav nav-tabs">
            <li class="nav-item">
                    <span class="nav-link"
                          @click="loadApps('installed')"
                          :class="activeTab === 'installed'? 'active' : ''">{{LANG.manager.installed_apps}}</span>
            </li>
            <li class="nav-item">
                    <span class="nav-link"
                          @click="loadApps('downloading')"
                          :class="activeTab === 'downloading'? 'active' : ''">{{LANG.manager.downloading_apps}}</span>
            </li>
            <li class="nav-item">
                    <span class="nav-link"
                          @click="loadApps('systems')"
                          :class="activeTab === 'systems'? 'active' : ''">{{LANG.manager.systems_apps}}</span>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active">
                <div class="apps" v-if="!_isEmptyObj(apps)">
                    <div class="app-item" v-for="(app,index) in apps">
                        <div class="icon">
                            <img :src="app.icon" :alt="app.name">
                            <div class="text">
                                <h2 class="name">{{app.name}}</h2>
                                <h3 class="info">{{LANG.manager.developer}}: {{app.developer}}</h3>
                                <h3 class="info">{{LANG.manager.version}}: {{app.version}}</h3>
                            </div>
                        </div>

                        <div class="action" v-if="!app.sys_app">
                            <router-link tag="span"
                                         :to="{name:'appManager-config',params:{package_name:app.package_name,config:app}}"
                                         class="btn"><i class="fa fa-cog"></i></router-link>
                            <router-link tag="span"
                                         :to="{name:'appManager-users',params:{package_name:app.package_name}}"
                                         class="btn"><i class="fa fa-users"></i></router-link>
                            <span class="btn"> <i class="fa fa-trash"></i></span>
                        </div>
                    </div>
                </div>
                <div class="empty" v-else>
                    <div>{{LANG.setting.appManager.empty_app}}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                activeTab: 'installed',
                apps: []
            }
        },
        methods: {
            loadApps(activeTab) {
                if (activeTab != null)
                    this.activeTab = activeTab;
                this.$http.get(this.URL.API + 'app/get/' + this.activeTab).then((json) => {
                    this.isLoading = false;
                    this.apps = json.data
                });
            },
            openConf(app) {

            }
        },
        created() {
            this.loadApps();
        }
    }
</script>
