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
            <div v-else-if="state==='error'" class="text">{{LANG.manager.installed_successfully}}</div>
            <div v-else class="text">{{LANG.manager.installed_successfully}}</div>
        </div>
        <div class="actions">
            <div v-if="state==='install'">
                <div class="btn-pin"
                     v-if="!!apps[app.package_name] && !!app.version_code && app.version_code > apps[app.package_name].version_code"
                     @click="update(app.state)">{{LANG.manager.update}}
                </div>
                <div class="btn-pin" v-else @click="install(app.state)">{{LANG.manager.install}}</div>
                <div class="btn-pin" v-else-if="app.state === 'manual'" @click="installPackage()">
                    {{LANG.manager.install}}
                </div>
                <div class="btn-pin" v-else-if="app.state === 'market'" @click="installApp()">{{LANG.manager.install}}
                </div>
                <div class="btn-pin" @click="close()">{{LANG.manager.cancel}}</div>
            </div>
            <div v-else-if="state==='complete'">
                <div @click="goToAppManager(!!app.uid ? 'app-templates' : 'app-details')" class="btn-pin">
                    <span v-if="!!app.uid"> {{LANG.manager.go_to_templates}}</span>
                    <span v-else> {{LANG.manager.go_to_app_manager}}</span>
                </div>
                <div class="btn-pin" @click="close()">{{LANG.manager.finish}}</div>
            </div>
            <div v-else-if="state==='error'">
                <div class="btn-pin" @click="close()">{{LANG.manager.close}}</div>
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
        computed: {
            apps: {
                get() {
                    return this.$store.state.apps;
                },
                set(val) {
                    this.$store.state.apps = val;
                }
            },
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
            update(state) {
                switch (state) {
                    case 'market':
                        this.updateMarket();
                        break;
                    case 'manual':
                        this.updateManual();
                        break;
                    case 'theme':
                        this.updateTheme();
                        break;
                }
            },
            install(state) {
                switch (state) {
                    case 'market':
                        this.installMarket();
                        break;
                    case 'manual':
                        this.installManual();
                        break;
                    case 'theme':
                        this.installTheme();
                        break;
                }
            },
            installMarket() {
                this._loading = true;
                this.state = 'installing';
                this.$http.get(this.URL.API + 'app/install/' + this.app.package_name).then((json) => {
                    this._loading = false;
                    this.getApps();
                    this.sendNotify(json.data, this.LANG.setting.market.install_app);
                });
            },
            installManual() {
                this._loading = true;
                this.state = 'installing';
                this.$http.get(this.URL.API + 'app/installPackage/' + this.app.filename).then((json) => {
                    this._loading = false;
                    this.getApps();
                    this.sendNotify(json.data, this.LANG.setting.market.install_app);
                });
            },
            installTheme() {
                this._loading = true;
                this.state = 'installing';
                this.$http.get(this.URL.API + 'template/install/' + this.app.uid + '/' + this.app.package_name).then((json) => {
                    this._loading = false;
                    this.sendNotify(json.data, this.LANG.manager.install);
                });
            },
            updateMarket() {
                this._loading = true;
                this.state = 'installing';
                this.$http.get(this.URL.API + 'app/update/' + this.app.package_name).then((json) => {
                    this._loading = false;
                    this.getApps();
                    this.sendNotify(json.data, this.LANG.manager.update_app);
                });
            },
            updateManual() {
                this._loading = true;
                this.state = 'installing';
                this.$http.get(this.URL.API + 'app/updatePackage/' + this.app.filename).then((json) => {
                    this._loading = false;
                    this.getApps();
                    this.sendNotify(json.data, this.LANG.manager.update_app);
                });
            },
            updateTheme() {
            },
            sendNotify(data, title) {
                console.log(title);
                if (data.status) {
                    this.state = 'complete';
                    this._notify(title, data.result, 'success');
                } else {
                    this.state = 'error';
                    this._notify(title, data.result, 'danger');
                }
            }
        }
    }
</script>