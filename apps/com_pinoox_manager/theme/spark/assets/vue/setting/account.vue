<template>
    <div class="content" data-simplebar>
        <div class="header">
           <div class="text">
               <h1>{{LANG.manager.account}}</h1>
           </div>
        </div>
        <div class="page">
            <div class="config">
                <h2 class="title">{{LANG.setting.account.profile_image}}</h2>

                <div class="image-profile">
                    <img :src="USER.avatar_thumb" @click="$refs.avatarInput.click()">

                    <div v-if="isLoadingAvatar" class="actions add pin-loader"><i class="fa fa-spinner"></i></div>
                    <div v-else-if="USER.isAvatar" class="actions">
                        <span @click="deleteAvatar()" v-if="USER.isAvatar" class="remove"><i class="fa fa-trash"></i> {{LANG.manager.delete}} </span>
                        <span class="change" @click="$refs.avatarInput.click()"><i
                                class="fa fa-image"></i> {{LANG.manager.edit}} </span>
                    </div>
                    <div v-else class="actions add">
                        <span class="change" @click="$refs.avatarInput.click()"><i
                                class="fa fa-image"></i> {{LANG.manager.add}}  </span>
                    </div>

                    <input v-show="false" ref="avatarInput" type="file"
                           @change="changeAvatar()"
                           accept=".jpg, .jpeg, .png"/>
                </div>


            </div>
            <div class="config" @keypress.enter="changeInfo()">
                <h2 class="title">{{LANG.setting.account.account_information}}</h2>
                <div class="form-group col-sm-5">
                    <label>{{LANG.user.first_name}}</label>
                    <input v-model="params.fname" type="text" class="form-control" :placeholder="LANG.user.first_name">
                </div>
                <div class="form-group col-sm-5">
                    <label>{{LANG.user.last_name}}</label>
                    <input v-model="params.lname" type="text" class="form-control" :placeholder="LANG.user.last_name">
                </div>
                <div class="form-group col-sm-5">
                    <label>{{LANG.user.username}}</label>
                    <input type="text" class="form-control" :placeholder="LANG.user.username" v-model="params.username">
                </div>
                <div class="form-group col-sm-5">
                    <label>{{LANG.user.email}}</label>
                    <input type="text" class="form-control" :placeholder="LANG.user.email" v-model="params.email">
                </div>
                <div class="form-group col-sm-5">
                    <div v-if="isLoadingInfo" class="btn-pin pin-loader"><i class="fa fa-spinner"></i></div>
                    <div v-else @click="changeInfo()" class="btn-pin"><i class="fa fa-save"></i> {{LANG.manager.save}}
                    </div>
                </div>
            </div>
            <div class="config" @keypress.enter="changePassword()">
                <h2 class="title">{{LANG.user.password}}</h2>
                <div class="form-group col-sm-5">
                    <input v-model="params.old_password" type="password" class="form-control"
                           :placeholder="LANG.user.old_password">
                </div>
                <div class="form-group col-sm-5">
                    <input v-model="params.new_password" type="password" class="form-control"
                           :placeholder="LANG.user.new_password">
                </div>
                <div class="form-group col-sm-5">
                    <input v-model="params.valid_password" type="password" class="form-control"
                           :placeholder="LANG.user.valid_password">
                </div>

                <div class="form-group col-sm-5">
                    <div v-if="isLoadingPass" class="btn-pin pin-loader"><i class="fa fa-spinner"></i></div>
                    <div v-else @click="changePassword()" class="btn-pin"><i class="fa fa-save"></i>
                        {{LANG.manager.save}}
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>


<script>
    import {mapState} from 'vuex';

    export default {
        data() {
            return {
                params: {},
                isLoadingPass: false,
                isLoadingInfo: false,
                isLoadingAvatar: false,
            }
        },
        created() {
            this.params = {...this.USER};
        },
        computed: {
            ...mapState({
                background: state => state.options.background,
            }),
        },
        methods: {
            deleteAvatar() {
                this.isLoadingAvatar = true;
                this.$http.get(this.URL.API + 'user/deleteAvatar/').then((json) => {
                    this.isLoadingAvatar = false;

                    if (json.data.status) {
                        this.USER.avatar = json.data.result.avatar;
                        this.USER.avatar_thumb = json.data.result.avatar_thumb;
                        this.USER.isAvatar = false;
                    }
                });
            },
            changeAvatar() {
                this.isLoadingAvatar = true;

                let file = this.$refs.avatarInput.files[0];
                let formData = new FormData();
                formData.append('avatar', file);
                this.$http.post(this.URL.API + 'user/changeAvatar/', formData).then((json) => {
                    this.isLoadingAvatar = false;

                    if (json.data.status) {
                        this.USER.avatar = json.data.result.avatar;
                        this.USER.avatar_thumb = json.data.result.avatar_thumb;
                        this.USER.isAvatar = true;
                    }
                });
            },
            changePassword() {
                this.isLoadingPass = true;
                this.$http.post(this.URL.API + 'user/changePassword/', {
                    old_password: this.params.old_password,
                    new_password: this.params.new_password,
                    valid_password: this.params.valid_password,
                }).then((json) => {
                    this.isLoadingPass = false;

                    if (json.data.status) {
                        this.params.old_password = null;
                        this.params.new_password = null;
                        this.params.valid_password = null;
                        this._notify(this.LANG.setting.account.account_panel, this.LANG.setting.account.password_changed_successfully, 'success');
                    } else {
                        this._notify(this.LANG.setting.account.account_panel, json.data.result, 'danger');
                    }
                });
            },
            changeInfo() {
                this.isLoadingInfo = true;

                this.$http.post(this.URL.API + 'user/changeInfo/', {
                    fname: this.params.fname,
                    lname: this.params.lname,
                    email: this.params.email,
                    username: this.params.username,
                }).then((json) => {
                    if (json.data.status) {
                        this.isLoadingInfo = false;
                        this.USER.fname = this.params.fname;
                        this.USER.lname = this.params.lname;
                        this.USER.email = this.params.email;
                        this.USER.username = this.params.username;
                        this._notify(this.LANG.setting.account.account_panel, this.LANG.manager.saved_successfully, 'success');

                    } else {
                        this._notify(this.LANG.setting.account.account_panel, json.data.result, 'danger');
                    }
                });
            },
        }
    }
</script>

<style>
    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .pin-loader .fa-spinner {
        -webkit-animation: spin 4s linear infinite;
        -moz-animation: spin 4s linear infinite;
        animation: spin 4s linear infinite;
    }
</style>
