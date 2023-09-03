<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page">
                    <h1 class="title">{{LANG.user.info_admin}}</h1>
                    <h2 class="description">{{LANG.user.info_admin_description}}</h2>
                    <div class="box">
                        <div v-if="isErr" class="row col-sm-12">
                            <span class="badge badge-danger mt-2 mb-4">{{err}}</span>
                        </div>
                        <div class="form" data-simplebar data-simplebar-auto-hide="false">
                            <div class="container" @keypress.enter="next()">
                                <div class="row form-group">
                                    <label class="col-sm-3">{{LANG.user.name}}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="user.fname" type="text" name="fname"
                                               class="pin-input form-control ltr"
                                               placeholder="first name">
                                    </div>
                                </div>

                                <div class="row form-group">
                                    <label class="col-sm-3">{{LANG.user.family_name}}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="user.lname" type="text" name="lname"
                                               class="pin-input form-control ltr"
                                               placeholder="last name">
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <label class="col-sm-3">{{LANG.user.email}}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="user.email" type="text" name="email"
                                               class="pin-input form-control ltr"
                                               placeholder="email">
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <label class="col-sm-3">{{LANG.user.username}}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="user.username" type="text" name="username"
                                               class="pin-input form-control ltr"
                                               placeholder="username">
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <label class="col-sm-3">{{LANG.user.password}}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="user.password" type="text" name="password"
                                               class="pin-input form-control ltr"
                                               placeholder="password">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <br>
                    <span @click="prev()" class="btn btn-outline-light pin-btn">{{LANG.install.back}}
                    </span>
                    <span v-if="!isLoading" @click="next()"
                          class="btn btn-light pin-btn">{{LANG.install.setup}}</span>
                    <span v-else class="btn btn-light pin-btn pin-loading"><i class="fa fa-spinner"></i></span>
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
                err: null,
            }
        },
        computed: {
            isErr() {
                return this.err !== null && !!this.err;
            },
            db() {
                return this.$store.state.db;
            },
            user: {
                get() {
                    return this.$store.state.user;
                },
                set(val) {
                    this.$store.state.user = val;
                }
            }
        },
        props: {
            steps: {
                type: Array,
            },
        },
        methods: {
            next() {
                this.isLoading = true;
                this.$http.post(this.URL.API + 'setup', {
                    db: this.db,
                    user: this.user,
                }).then((json) => {
                    let isInstall = !json.data || typeof json.data !== "object" || json.data.status;
                    if (isInstall) {
                        setTimeout(()=>{
                            this.isLoading = false;
                            this._redirect(this.URL.SITE);
                        },1000);
                    } else {
                        this.isLoading = false;
                        this.err = json.data.result;
                    }
                });
            },
            prev() {
                this.$router.replace({name: 'db'});
            },
        },
        created() {
            this.$emit('update:steps', [true,true,true]);
        }
    }
</script>