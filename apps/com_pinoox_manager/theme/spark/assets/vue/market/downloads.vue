<template>
    <div class="pin-packages">
        <div v-if="isLoading" class="pin-spinner"></div>
        <div class="empty" v-else-if="apps.length <=0">
            <div>{{LANG.setting.appManager.empty_app}}</div>
        </div>
        <div class="apps" v-else>
            <div class="app-item" v-for="(app,index) in apps">
                <div class="icon">
                    <img :src="app.icon" :alt="app.name">
                    <div class="text">
                        <h2 class="name">{{app.name}}</h2>
                        <h3 class="info">{{LANG.manager.version}}: {{app.version}}</h3>
                        <h3 class="info">{{LANG.manager.file_size}}: {{app.size}}</h3>
                    </div>
                </div>
                <div class="action">
                    <span class="btn-pin" @click="_openFloatInstaller(app)">{{LANG.manager.install}}</span>
                    <span class="btn-pin" @click="deleteFile(index)">{{LANG.manager.delete}}</span>
                </div>
            </div>
        </div>

    </div>
</template>
<script>

    export default {
        data() {
            return {
                isLoading: false,
                apps: [],
            }
        },
        created() {
            this.getDownloads();
        },
        methods: {
            getDownloads() {
                this.isLoading = true;
                this.$http.get(this.URL.API + 'market/getDownloads').then((json) => {
                    this.isLoading = false;
                    this.apps = json.data
                });
            },
            deleteFile(index) {
                let app = this.apps[index];
                this._notify(this.LANG.manager.alert, this.LANG.manager.are_you_sure_delete_file, '', [
                    {
                        text: this.LANG.manager.delete,
                        func: () => {
                            this._loading = true;
                            this.$http.post(this.URL.API + 'market/deleteDownload', {package_name: app.package_name}).then((json) => {
                                this._loading = false;
                                if (json.data.status) {
                                    this.$delete(this.apps, index);
                                }
                            });
                        }
                    }, {
                        text: this.LANG.manager.cancel,
                        func: () => {
                        }
                    }]);
            }
        }
    }
</script>