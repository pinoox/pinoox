<template>
    <div class="content" ref="content">
        <div class="header">
            <div class="text">
                <h1>{{LANG.manager.package_files}}</h1>
                <h2>{{LANG.manager.package_files_help }}</h2>
            </div>
        </div>
        <div class="page" data-simplebar data-simplebar-auto-hide="false">
            <div class="pin-packages">
                <div v-if="isLoading" class="pin-spinner"></div>
                <div class="empty" v-else-if="files.length <=0">
                    <div>{{LANG.manager.empty_files_package}}</div>
                </div>
                <div class="apps" v-else>
                    <div v-for="(file,index) in files">
                      <div class="app-item">
                        <div class="icon">
                          <img :src="file.type === 'app'? file.icon : file.cover" :alt="file.name">
                          <div class="text">
                            <h2 class="name">{{file.filename}} ({{LANG.manager[file.type]}})</h2>
                            <h3 class="info">{{file.name}} (v{{file.version}})</h3>
                            <h3 class="info">{{LANG.manager.file_size}}: {{file.size}}</h3>
                            <h3 class="info">{{file.app}}</h3>
                          </div>
                        </div>
                        <div class="action">
                          <span class="btn-pin" @click="setup(index)">{{LANG.manager.install}}</span>
                          <span class="btn-pin" @click="deleteFile(index)">{{LANG.manager.delete}}</span>
                        </div>
                      </div>
                    </div>
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
            this.getFiles();
        },
        methods: {
            setup(index)
            {
                let file = this.files[index];
                let state = file.type === 'app'? 'manual' : 'manual-theme';
                this._openFloatInstaller(file,state,() => {
                    this.$delete(this.files, index);
                });
            },
            getFiles() {
                this.isLoading = true;
                this.$http.get(this.URL.API + 'app/files/').then((json) => {
                    this.isLoading = false;
                    this.files = json.data;
                });
            },
            deleteFile(index)
            {
                let file = this.files[index];
                this._notify(this.LANG.manager.alert, this.LANG.manager.are_you_sure_delete_file, '', [
                    {
                        text: this.LANG.manager.delete,
                        func: () => {
                            this._loading = true;
                            this.$http.post(this.URL.API + 'app/deleteFile', {filename: file.filename}).then((json) => {
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