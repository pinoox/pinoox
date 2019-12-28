<template>
    <div class="app-details"
         :class="selectedApp==null ? '':'animated faster zoomInDown'">
        <span @click="close()" class="_close"><i class="fa fa-times"></i></span>
        <div class="header"></div>
        <div class="content pretty-scroll">
            <div class="sections"
                 v-if="selectedApp!=null">
                <div class="app">
                    <img :src="selectedApp.icon" class="icon">
                    <div class="text">
                        <span class="app-name">{{selectedApp.name}}</span>
                        <router-link v-if="selectedApp.router" tag="span" :to="{name:'setting-router'}" class="btn"
                                     v-bind:title="LANG.manager.router"><i class="fas fa-code-branch"></i></router-link>
                        <router-link :to="{name:'appManager-config',params:{package_name:selectedApp.package_name}}" class="btn" v-bind:title="LANG.manager.app_manager"><i
                                class="fas fa-cogs"></i></router-link>
                        <a target="_blank" class="btn" :href="URL.APP+ 'app/'+selectedApp.package_name" :title="LANG.manager.preview"><i
                                class="fa fa-eye"></i></a>
                    </div>


                </div>
                <div class="info">
                    <ul>
                        <li>
                            <span class="name">{{LANG.manager.developer}}</span>
                            <span class="value">{{selectedApp.developer}}</span>
                        </li>
                        <li>
                            <span class="name">{{LANG.manager.version}}</span>
                            <span class="value">{{selectedApp.version}}</span>
                        </li>
                        <li>
                            <span class="name">{{LANG.manager.package_name}}</span>
                            <span class="value">{{selectedApp.package_name}}</span>
                        </li>
                        <li>
                            <span class="name">{{LANG.manager.description}}</span>
                            <span class="value">{{selectedApp.description}}</span>
                        </li>

                    </ul>
                </div>
            </div>
            <div class="routes" v-if="selectedApp.router">
                <div v-if="selectedApp.routes.length>0">
                    <a target="_blank" v-for="r in selectedApp.routes"
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
    import {mapMutations} from 'vuex';
    import PlainDraggable from 'plain-draggable';

    export default {
        name: "AppDetails",
        props: ['app'],
        computed: {
            selectedApp: {
                get: function () {
                    return this.app;
                },
                set: function (val) {
                    this.app = val;
                }
            }
        },
        data() {
            return {}
        },
        mounted() {
            let dragElements = document.getElementsByClassName('app-details');
            dragElements.forEach((item) => {
                new PlainDraggable(item);
            });
        },
        methods: {
            ...mapMutations(['getApps']),
            close() {
                this.$emit('close');
            },
            deleteApp() {
                this._notify(this.LANG.manager.alert, this.LANG.manager.are_you_sure_delete_app, null, [
                    {
                        text: this.LANG.manager.delete,
                        func: () => {
                            this._loading = true;
                            this.$http.post(this.URL.API + 'app/remove/', {
                                packageName: this.selectedApp.package_name,
                            }).then((json) => {
                                this._loading = false;
                                this.$delete(this.$store.state.apps,this.selectedApp.package_name);
                                this.$emit('close');
                            });
                        }
                    },
                    {
                        text: this.LANG.manager.cancel,
                        func: () => {
                        }
                    }
                ]);
            }
        }
    }
</script>