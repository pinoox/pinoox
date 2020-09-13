<template>
    <div class="pin-packages">
        <div v-if="isLoading" class="pin-spinner"></div>
        <div class="empty" v-else-if="!files || files.length <=0">
            <div>{{LANG.setting.appManager.empty_app}}</div>
        </div>
        <div class="apps" v-else>
            <div class="app-item" v-for="(file,index) in files">
                <div class="icon">
                    <img :src="file.market.icon" :alt="file.name">
                    <div class="text">
                        <h2 class="name">{{file.market.name}}</h2>
                        <h3 class="info">{{LANG.manager.version}}: {{file.version}}</h3>
                        <h3 class="info">{{LANG.manager.file_size}}: {{file.size}}</h3>
                    </div>
                </div>
                <div class="action">
                    <span v-if="!!apps[file.package_name] && file.version_code > apps[file.package_name].version_code" class="btn-pin" @click="_openFloatInstaller(file,'market')">{{LANG.manager.update}}</span>
                    <span v-else class="btn-pin" @click="_openFloatInstaller(file,'market')">{{LANG.manager.install}}</span>

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
                files: [],
            }
        },
        created() {
            this.getDownloads();
        },
        computed:{
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
            getDownloads() {
                this.isLoading = true;
                this.$http.get(this.URL.API + 'market/getDownloads').then((json) => {
                    this.isLoading = false;
                    this.files = json.data
                });
            },
            deleteFile(index) {
                let file = this.files[index];
                this._notify(this.LANG.manager.alert, this.LANG.manager.are_you_sure_delete_file, '', [
                    {
                        text: this.LANG.manager.delete,
                        func: () => {
                            this._loading = true;
                            this.$http.post(this.URL.API + 'market/deleteDownload', {package_name: file.package_name}).then((json) => {
                                this._loading = false;
                                if (json.data.status) {
                                    this.$delete(this.files, index);
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