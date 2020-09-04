<template>
    <div class="content" data-simplebar>
        <div class="header" v-if="app!=null">
            <div class="text">
                <h1>{{LANG.manager.app}} {{app.name}}</h1>
                <h2>{{app.description}}</h2>
            </div>
        </div>
        <div class="page">
            <div class="app-manager" v-if="app!=null">
                <div class="info">
                    <div class="item">
                        <div class="label">{{LANG.manager.app_name}}</div>
                        <div class="text">{{app.name}}</div>
                    </div>
                    <div class="item">
                        <div class="label">{{LANG.manager.package_name}}</div>
                        <div class="text">{{app.package_name}}</div>
                    </div>
                    <div class="item">
                        <div class="label">{{LANG.manager.developer}}</div>
                        <div class="text">{{app.developer}}</div>
                    </div>
                    <div class="item">
                        <div class="label">{{LANG.manager.version}}</div>
                        <div class="text">{{app.version_code}}</div>
                    </div>
                    <div class="item">
                        <div class="label">{{LANG.manager.description}}</div>
                        <div class="text">{{app.description}}</div>
                    </div>
                    <div v-if="app.router && app.routes.length>0" class="item mt-2">
                        <div class="label">{{LANG.manager.addresses}}</div>
                    </div>
                </div>
            </div>
            <div class="app-routes" v-if="app.router">
                <div v-if="app.routes.length>0">
                    <a target="_blank" v-for="r in app.routes"
                       :href="URL.SITE+(r==='*' ? '' :r)">{{URL.SITE}}{{r==='*'
                        ? '' : r}}</a>
                </div>
                <div v-else>
                    <div class="message">
                        <div class="text">{{LANG.manager.app_routes_empty}}</div>
                        <router-link :to="{name:'setting-router'}" class="pin-btn">{{LANG.manager.router}}
                        </router-link>
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
            return {}
        },
        computed: {
            app: {
                get() {
                    return !!this.$parent.selectedApp ? this.$parent.selectedApp : null;
                },
                set(val) {
                    this.$parent.selectedApp = val;
                }
            },
            apps: {
                get() {
                    return this.$store.state.apps;
                }
            },
        },
        methods: {
            onUpdateAppInfo() {
                this.app = this.apps[this.package_name];
            }
        },
        created() {
            this.onUpdateAppInfo();
        },
        watch: {
            $route(to, from) {
                if (to.params.package_name !== this.app.package_name)
                    this.onUpdateAppInfo();
            }
        }

    }
</script>
