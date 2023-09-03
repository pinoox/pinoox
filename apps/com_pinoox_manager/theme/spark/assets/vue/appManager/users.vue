<template>
    <div class="content" data-simplebar>
        <div class="header" v-if="app!=null">
            <div class="text">
                <h1>{{LANG.manager.app}} {{app.name}}</h1>
                <h2>{{app.description}}</h2>
            </div>
        </div>
        <div class="page">
            <div class="users" v-if="users!==null && !!users && users.length > 0">
                <div class="item" v-for="(u,index) in users">
                    <div class="image">
                        <img :src="u.avatar_thumb">
                    </div>
                    <div class="text">{{u.full_name}}</div>
                    <div class="text">{{u.email}}</div>
                    <div class="text">{{u.register_date_fa}}</div>
                    <div class="text">{{u.status_fa}}</div>
                </div>
            </div>
            <div v-else class="empty">
                <div>{{LANG.setting.appManager.empty_users}}</div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['package_name'],
        computed: {
            app: {
                get() {
                    return this.$parent.app;
                },
                set(val) {
                    this.$parent.app = val;
                }
            },
        },
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
        },

    }
</script>
