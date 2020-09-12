<template>
    <div v-if="app!=null"
         id="float-installer"
         class="float-installer"
         :class="app==null ? '':'animated faster zoomInDown'">
        <div class="header">
            <div class="header-content">
                <div v-if="!!app.uid">
                    {{app.template_name}}
                </div>
                <div v-else>
                    <img :src="app.icon" class="app-icon">
                    {{app.app_name}}
                </div>
            </div>
            <span @click="close()" class="_close"><i class="fa fa-times"></i></span>
        </div>
        <div class="content pretty-scroll">

            <div v-if="state==='install'" class="text">
                <span v-if="!!app.uid">{{LANG.manager.float_installer_message_template}}</span>
                <span v-else>{{LANG.manager.float_installer_message_app}}</span>
            </div>
            <div v-else-if="state==='installing'" class="text">{{LANG.manager.please_wait_until_complete}}</div>
            <div v-else class="text">{{LANG.manager.installed_successfully}}</div>
        </div>
        <div class="actions">
            <div v-if="state==='install'">
                <div class="btn-pin" v-if="!!app.uid" @click="installTemplate()">{{LANG.manager.install}}</div>
                <div class="btn-pin" v-else @click="installApp()">{{LANG.manager.install}}</div>
                <div class="btn-pin" @click="close()">{{LANG.manager.cancel}}</div>
            </div>
            <div v-else-if="state==='complete'">
                <div @click="goToAppManager(!!app.uid ? 'app-templates' : 'app-details')" class="btn-pin">
                   <span v-if="!!app.uid"> {{LANG.manager.go_to_templates}}</span>
                   <span v-else> {{LANG.manager.go_to_app_manager}}</span>
                </div>
                <div class="btn-pin" @click="close()">{{LANG.manager.finish}}</div>
            </div>
        </div>
    </div>
</template>

<script>
    import PlainDraggable from 'plain-draggable';
    import {mapMutations} from "vuex";

    export default {
        name: "FloatInstaller",
        props: ['app'],
        data() {
            return {
                state: 'install',//install, installing, complete
            }
        },
        mounted() {
            let drag = document.getElementById('float-installer');
            new PlainDraggable(drag);
        },
        methods: {
            ...mapMutations(['getApps']),
            close() {
                this.$emit('close');
            },
            goToAppManager(route) {
                this.close();
                this.$router.push({name: route, params: {package_name: this.app.package_name}});
            },
            installApp() {
                this._loading = true;
                this.state = 'installing';
                this.$http.get(this.URL.API + 'app/install/' + this.app.package_name).then((json) => {
                    this._loading = false;
                    this.getApps();
                    this.state = 'complete';
                    if (json.data.status)
                        this._notify(this.LANG.manager.installed_successfully, '', 'success');

                });
            },
            installTemplate() {
                this._loading = true;
                this.state = 'installing';
                this.$http.get(this.URL.API + 'template/install/' + this.app.uid + '/' + this.app.package_name).then((json) => {
                    this._loading = false;
                    this.state = 'complete';
                    if (json.data.status)
                        this._notify(this.LANG.manager.installed_successfully, '', 'success');

                });
            },
        }
    }
</script>