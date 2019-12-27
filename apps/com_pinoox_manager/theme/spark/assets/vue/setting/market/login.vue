<template>
    <div class="market-login">
        <span @click="$router.go(-1)" class="return pin-btn"><i
                class="fa fa-chevron-right"></i> {{LANG.manager.return}}
        </span>
        <br>
        <br>
        <div class="message">
            <h2>{{LANG.manager.required_login_for_download_app}}</h2>
            <a href="https://www.pinoox.com/user/register" target="_blank">{{LANG.manager.create_pinoox_account}}</a>
        </div>

        <div class="form" @keyup.enter="login()">
            <img class="user-image" alt="pinoox" src="@img/logo/logo-256.png">
            <div class="user-input">
                <input v-model="params.email" type="text" :placeholder="LANG.user.username_or_email">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-input">
                <input v-model="params.password" type="password" :placeholder="LANG.user.password">
                <i class="fas fa-lock"></i>
            </div>
            <br>
            <div v-if="isLoading" class="user-btn pin-loader"><i class="fa fa-spinner"></i></div>
            <a v-else @click="login()" class="user-btn">{{LANG.user.login}}</a>
        </div>

    </div>
</template>
<script>
    export default {
        data() {
            return {
                isLoading: false,
                params: {
                    email: null,
                    password: null,
                }
            }
        },
        computed: {
            user: {
                set(val) {
                    this.$store.state.pinoox_auth = val;
                },
                get() {
                    return this.$store.state.pinoox_auth;
                }
            }
        },
        methods: {
            login() {
                this.isLoading = true;
                this.$http.post(this.URL.API + 'account/login', this.params).then((json) => {
                    this.isLoading = false;
                    if (json.data.status) {
                        this.user = json.data.result;
                        localStorage.setItem('pinoox_auth', JSON.stringify(this.user));
                        this.$router.replace({path: 'account'});
                    } else {
                        this._notify(this.LANG.user.login_to_pinoox, json.data.result);
                    }
                });
            }
        }
    }
</script>