<template>
    <div class="content" data-simplebar>
        <div class="header">
            <div class="text">
                <h1>{{LANG.manager.manual_installation}}</h1>
            </div>
        </div>
        <div class="page">
            <div class="manual-installation">
                <div class="upload-wrapper">
                    <div class="upload-panel">
                        <i class="icon fas fa-cloud-upload-alt"></i>
                        <div class="text">{{LANG.manager.upload_pinoox_package_for_installing}}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['package_name'],
        data() {
            return {
                users: null
            }
        },
        methods: {
            getUsers() {
                this.$http.get(this.URL.API + 'user/getUsers/' + this.package_name).then((json) => {
                    this.users = json.data;
                });
            }
        },
        created() {
            this.getUsers();
        }
    }
</script>
