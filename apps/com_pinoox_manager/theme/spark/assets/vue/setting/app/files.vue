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
                <div class="files-home" v-else>
                    <div class="files-list">
                        <div class="file-item" v-for="(file,index) in files">
                            <div class="app-icon">
                                <img :src="file.icon" alt="app.app_name">
                                <span class="version">v{{file.version}}</span>
                            </div>
                            <div class="name">{{file.name}}</div>
                            <div class="filename">({{file.filename}})</div>
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
        }
    }
</script>