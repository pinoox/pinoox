<template>
    <div class="content" data-simplebar>
        <div class="header">
            <div class="text">
                <h1>{{LANG.manager.manual_installation}}</h1>
            </div>
        </div>
        <div class="page" @dragover.prevent @drop.prevent>
            <div class="manual-installation" @drop="handleFileDrop">
                <div class="upload-wrapper">
                    <div class="upload-panel" @click="selectFile" v-if="!isUploadManual">
                        <i class="icon fas fa-cloud-upload-alt"></i>
                        <div class="text">{{LANG.manager.upload_pinoox_package_for_installing}}</div>
                        <input ref="file" type="file" name="file-input" @change="handleFileInput" multiple>
                    </div>

                    <div class="upload-panel" v-if="isUploadManual">
                        <vm-progress v-if="percent>=100" type="circle" :percentage="percent" status="success"></vm-progress>
                        <vm-progress v-else type="circle" :percentage="percent"></vm-progress>
                        <span @click="cancel" class="btn-pin">{{LANG.manager.cancel}}</span>
                    </div>

                </div>

                <div class="show-errs" v-if="!!message">
                    <span class="badge badge-info">{{LANG.manager.latest_status}}</span>
                    <div class="alert alert-info">{{message}}</div>
                    <div class="alert alert-danger" v-if="!!errs && errs.length >0" v-for="err in errs ">{{err}}</div>
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
                files: [],
            }
        },
        computed:{
            isUploadManual(){
                    return this.$store.state.manual.percent > 0;
            },
            percent:{
                get(){
                    return this.$store.state.manual.percent;
                },
                set(val)
                {
                    this.$store.state.manual.percent = val;
                },
            },
            xhr:{
                get(){
                    return this.$store.state.manual.xhr;
                },
                set(val)
                {
                    this.$store.state.manual.xhr = val;
                },
            },
            message:{
                get(){
                    return this.$store.state.manual.message;
                },
                set(val)
                {
                    this.$store.state.manual.message = val;
                },
            },
            errs:{
                get(){
                    return !!this.$store.state.manual.errs? this.$store.state.manual.errs : null;
                },
                set(val)
                {
                    this.$store.state.manual.errs = val;
                },
            }
        },
        methods: {
            handleFileDrop(e) {
                let droppedFiles = e.dataTransfer.files;
                if (!droppedFiles) return;
                ([...droppedFiles]).forEach(f => {
                    this.files.push(f);
                });
                this.uploadFiles();
            },
            selectFile() {
                this.$refs.file.click();
            },
            handleFileInput(e) {
                let files = e.target.files;
                if (!files) return;
                ([...files]).forEach(f => {
                    this.files.push(f);
                });
                this.uploadFiles();
            },
            cancel()
            {
                this.setPercent(0);
                this.xhr.cancel('stop upload');
            },
            setPercent(percent)
            {
                this.percent = percent;
                if(percent > 0)
                {
                    this.pushToNotifications({
                        key:'setup-manual',
                        title: this.LANG.manager.manual_installation,
                        message:this.LANG.manager.process_upload_waiting,
                        route:{name:'apps-manual'},
                        percent:percent,
                    })
                }
                else
                {
                    this.closeFromNotifications('setup-manual');
                }
            },
            uploadFiles() {
                let data = new FormData();
                let i = 0;
                let files = this.files;
                ([...files]).forEach(f => {
                    data.append('files[' + i + ']', f);
                    i++;
                });

                this.files = [];
                this.message = null;
                this.errs = null;
                this.xhr = this.$http.CancelToken.source();
                this.$http.post(this.URL.API + 'app/filesUpload/', data, {
                 cancelToken: this.xhr.token ,
                    onUploadProgress: (progressEvent) => {
                        let percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        this.setPercent(percent);
                    }
                }).then((json) => {
                    this.setPercent(0);
                    if(json.data.status)
                    {
                        this.message = json.data.result;
                        this._notify(this.LANG.manager.upload_files, this.message, 'success');
                        this.$router.replace({name:'apps-files'}).catch();
                    }
                    else
                    {
                        this.message = json.data.result.message;
                        this.errs = json.data.result.errs;
                        this._notify(this.LANG.manager.upload_files, this.message, 'danger');
                    }
                }).catch(function (thrown) {
                });
            },
        },
    }
</script>
