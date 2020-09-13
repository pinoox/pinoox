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
                    <div class="app-item" v-for="(file,index) in files">
                        <div class="icon">
                            <img :src="file.icon" :alt="file.name">
                            <div class="text">
                                <h2 class="name">{{file.filename}}</h2>
                                <h3 class="info">{{file.name}} (v{{file.version}})</h3>
                                <h3 class="info">{{LANG.manager.file_size}}: {{file.size}}</h3>
                            </div>
                        </div>
                        <div class="action">
                            <span class="btn-pin" @click="_openFloatInstaller(file)">{{LANG.manager.install}}</span>
                            <span class="btn-pin" @click="deleteFile(index)">{{LANG.manager.delete}}</span>
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
                keyword: '',
            }
        },
        created() {
            this.getFiles();
        },
        methods: {
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