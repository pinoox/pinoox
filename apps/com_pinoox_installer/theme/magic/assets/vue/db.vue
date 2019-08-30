<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page">
                    <h1 class="title">{{LANG.install.db_info}}</h1>
                    <h2 class="description">{{LANG.install.db_info_description}}</h2>
                    <div class="box">
                        <div v-if="isErr" class="row col-sm-12">
                            <span class="badge badge-danger mt-2 mb-4">{{LANG.install.err_connect_to_database}}</span>
                        </div>
                        <div class="form" data-simplebar data-simplebar-auto-hide="false">
                            <div class="container" @keypress.enter="next()">
                                <div class="row form-group">
                                    <label class="col-sm-3">{{LANG.install.db_host}}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="params.host" type="text" name="host"
                                               class="pin-input form-control ltr"
                                               placeholder="localhost">
                                    </div>
                                </div>

                                <div class="row form-group">
                                    <label class="col-sm-3">{{LANG.install.db_name}}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="params.database" type="text" name="database"
                                               class="pin-input form-control ltr" placeholder="database">
                                    </div>
                                </div>

                                <div class="row form-group">
                                    <label class="col-sm-3">{{LANG.install.db_username}}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input type="text" v-model="params.username" name="username"
                                               class="pin-input form-control ltr"
                                               placeholder="username">
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <label class="col-sm-3">{{LANG.install.db_password}}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input type="text" v-model="params.password" name="password"
                                               class="pin-input form-control ltr" placeholder="password">
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <label class="col-sm-3">{{LANG.install.db_prefix}}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="params.prefix" type="text" name="prefix"
                                               class="pin-input form-control ltr"
                                               placeholder="prefix">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <span @click="prev()" class="btn btn-outline-light pin-btn">{{LANG.install.back}}
                    </span>
                    <span v-if="!isLoading" @click="next()"
                          class="btn btn-light pin-btn">{{LANG.install.continue}}</span>
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
                isErr: false,
            }
        },
        computed: {
            params: {
                get() {
                    return this.$store.state.db;
                },
                set(val) {
                    this.$store.state.db = val;
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
                this.$http.post(this.URL.API + 'checkDB/', this.params).then((json) => {
                    this.isLoading = false;

                    if (json.data.status) {
                        this.$router.replace({name: 'user'});
                    } else {
                        this.isErr = true;
                    }
                });
            },
            prev() {
                this.$router.replace({name: 'prerequisites'});
            },
        },
        created() {
            this.$emit('update:steps', [true,true]);
        }
    }
</script>