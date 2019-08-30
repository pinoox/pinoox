<template>
    <section>
        <div id="login">
            <div class="mask"></div>
            <div v-if="!err" class="user" @keypress.enter="login()">
                <img v-if="!isLock" class="user-image" alt="pinoox" src="@img/logo/logo-256.png">
                <img v-if="isLock" class="user-image" :src="USER.avatar_thumb" alt="user-profile-image">
                <span v-if="isLock" class="user-name">{{USER.full_name}}</span>
                <br>
                <div v-if="!isLock" class="user-input">
                    <input ref="username" v-model="params.username" type="text" :placeholder="LANG.user.username_or_email">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-input">
                    <input ref="password" v-model="params.password" type="password" :placeholder="LANG.user.password">
                    <i class="fas fa-lock"></i>
                </div>
                <br>

                <br>
                <div v-if="isLoading" class="user-btn pin-loader"><i class="fa fa-spinner"></i> </div>
                <a v-else @click="login()" class="user-btn">{{LANG.user.login}}</a>
            </div>
            <div v-if="err" class="user error" @keypress.enter="ok()">
                <span>{{err}}</span>
                <br>

                <br>
                <a @click="ok()" class="user-btn">{{LANG.manager.okay}}</a>
            </div>
            <div class="footer">
                <div class="menu">
                    <a v-if="isLock" @click="logout()" href="javascript:">
                        <i class="fa fa-power-off"></i>
                        <span>{{LANG.user.logout}}</span>
                    </a>
                </div>
            </div>

        </div>
    </section>
</template>

<script>
    import Vue from 'vue';
    import {mapMutations} from 'vuex';

    export default {
        mounted()
        {
            window.addEventListener('keypress', (e) => {
                if (this.err && e.key == 'Enter') {
                    this.ok();
                }
            });
        },
        data() {
            return {
                err: '',
                isLoading:false,
                params: {
                    username: '',
                    password: '',
                }
            }
        },
        methods: {
            ...mapMutations(['logout']),
            login: function () {
                this.isLoading = true;
                this.$http.post(this.URL.API + 'user/login', this.params).then((json) => {
                    this.isLoading = false;
                    if(json.data.status)
                    {
                        this.isLogin = true;
                        this.isLock = false;
                        this.USER = json.data.result;
                    }
                    else
                    {
                        this.isLogin = false;
                        this.err = json.data.result;
                    }
                });
            },
            ok()
            {
                this.err = '';

                Vue.nextTick(()=> {
                        this.$refs.password.focus();
                });


            }
        }
    };
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
        -webkit-animation:spin 4s linear infinite;
        -moz-animation:spin 4s linear infinite;
        animation:spin 4s linear infinite;
    }
</style>
