<template>
    <div>
        <router-link tag="span" :to="{name:'appManager-home'}" class="return pin-btn"><i
                class="fa fa-chevron-right"></i> {{LANG.manager.return}}
        </router-link>

        <div class="config" v-if="appConfig.hidden!=null">
            <h2 class="title">{{LANG.setting.appManager.opt_hidden}}
                <toggle-button class="switch" :value="appConfig.hidden" @change="toggleSwitch('hidden', true)"
                               :labels="false"/>
            </h2>
            <h3 class="description">{{LANG.setting.appManager.opt_hidden_text}}</h3>
        </div>
        <div class="config" v-if="appConfig.router!=null">
            <h2 class="title">{{LANG.setting.appManager.opt_router}}
                <toggle-button class="switch" :value="appConfig.router==='multiple'"
                               @change="toggleSwitch('router', 'single')"
                               :labels="false"/>
            </h2>
            <h3 class="description">{{LANG.setting.appManager.opt_router_text}}</h3>
        </div>

    </div>
</template>

<script>
    import {mapMutations} from 'vuex';

    export default {
        data() {
            return {
                appConfig: {}
            }
        },
        props: ['config', 'package_name'],
        methods: {
            ...mapMutations(['getApps']),
            toggleSwitch(key) {
                this.$http.post(this.URL.API + 'app/setConfig/' + this.package_name + "/" + key, {config: this.appConfig[key]}).then((json) => {
                    if (json.data.status) {
                        this.appConfig[key] = json.data.result;
                        this.getApps();
                    }
                });
            },
            getConfig() {
                this.$http.get(this.URL.API + 'app/getConfig/' + this.package_name).then((json) => {
                    this.appConfig = json.data;
                });
            }
        },
        created() {
            if (this.config == null) {
                this.getConfig();
            } else {
                this.appConfig = this.config;
            }
        }
    }
</script>
