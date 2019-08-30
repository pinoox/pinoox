<template>
    <div id="content" ref="content">
        <div class="header">
            <h1>{{LANG.manager.router}}</h1>
            <h2>{{LANG.setting.router.router_help }}</h2>
        </div>
        <div class="page" data-simplebar data-simplebar-auto-hide="false">
            <!-- choose app -->
            <div v-show="isLoading" class="pin-spinner"></div>
            <div v-show="!isLoading && isChooseApp" class="route-select-app">
                <div @click="isChooseApp=!isChooseApp" class="pin-btn">
                    <span><i class="fas fa-chevron-right"></i></span>
                </div>
                <input type="search" v-model="searchApp" class="pin-input "
                       :placeholder="LANG.setting.router.search_apps">
                <div class="result">
                    <div class="item" v-for="a in filteredApps" @click="setPackageName(a.package_name)" v-if="a.router">
                        <img :src="a.icon">
                        <span class="name">{{a.name ? a.name : 'app'}}</span>
                        <span class="package">{{a.package_name}}</span>
                    </div>
                </div>
            </div>
            <div  v-show="!isLoading && !isChooseApp">
                <!-- add new route button-->
                <div class="add-route">
                    <div v-if="!isAdd" @click="isAdd=!isAdd" class="pin-btn">
                        <span><i class="fa fa-plus"></i></span>
                    </div>
                </div>


                <!-- add new route form-->
                <div class="routes-list" v-if="isAdd" @keypress.enter="addRoute()">
                    <div class="route-item">
                        <div class="url">
                            <div class="base">{{URL.SITE}}</div>
                            <div class="alias"><input v-model="newAlias" type="text" class="pin-input"></div>
                        </div>

                        <div class="actions">
                        <span class="pin-btn" @click="addRoute()">  <i
                                class="fa fa-save"></i>
                        </span>
                            <span class="pin-btn" @click="isAdd=false"> <i class="fa fa-times"></i>
                        </span>
                        </div>

                    </div>
                </div>

                <!-- routes -->

                    <transition-group tag="div" class="routes-list" mode="out-in" enter-active-class="animated faster fadeIn"
                                leave-active-class="animated faster fadeOut">
                        <div :key="aliasName" class="route-item" v-for="(app, aliasName) in routes"
                             :class="['', (app.is_lock ? 'is_lock' : ''), (aliasName=='*'? 'default' : '')]">
                            <div class="actions">
                        <span v-if="!app.is_lock && aliasName!='*'" @click="removeRoute(aliasName)" class="pin-btn">  <i
                                class="fa fa-trash"></i> </span>
                            </div>
                            <a class="url" target="_blank" :href="URL.SITE + (aliasName==='*' ? '' : aliasName)">
                                <div class="base">{{URL.SITE}}</div>
                                <div class="alias">{{aliasName==='*' ? '' : aliasName}}</div>
                            </a>

                            <div class="icon" @click="chooseApp(app,aliasName)"><img
                                    :src="app.icon ? app.icon : URL.APP_ICON"><span>{{app.package ? (app.name ? app.name :  'app') : LANG.setting.router.choose_app}}</span>
                            </div>
                            <div v-if="aliasName=='*'" class="default-app">{{LANG.setting.router.default_app}}</div>

                        </div>

                    </transition-group>
            </div>

        </div>
    </div>
</template>


<script>
    import {mapGetters, mapState, mapMutations} from 'vuex';
    import SimpleBar from 'simplebar';

    export default {
        data() {
            return {
                routes: [],
                isAdd: false,
                isChooseApp: false,
                newAlias: null,
                selectedAlias: null,
                searchApp: '',
                isLoading:false,
            }
        },
        computed: {
            ...mapState({
                background: state => state.options.background,
            }),
            apps: {
                set(val) {
                    this.$store.state.apps = val;
                },
                get() {
                    return this.$store.state.apps;
                }
            },
            ...mapGetters(['appsArray']),
            routesArray() {
                return Object.values(this.routes).map(function (route) {
                    return route['package'];
                });
            },
            filteredApps() {
                return this.appsArray.filter((app) => {

                    return (app.router === 'multiple' || (!this.routesArray.includes(app.package_name))) && (this.searchApp == null || (app.package_name != null && app.package_name.toLowerCase().includes(this.searchApp.toLowerCase())) ||
                        (app.name != null && app.name.toLowerCase().includes(this.searchApp.toLowerCase())));
                });
            }
        },
        methods: {
            ...mapMutations(['getApps']),
            removeRoute(aliasName) {
                this._notify(this.LANG.manager.alert, this.LANG.manager.are_you_sure_delete_app, '', [
                    {
                        text: this.LANG.manager.delete,
                        func: () => {
                            this._loading = true;
                            this.$http.post(this.URL.API + 'router/remove', {aliasName: aliasName}).then((json) => {
                                this._loading = false;
                                if (json.data.status) {
                                    this.$delete(this.routes, aliasName);
                                    this.getApps();
                                }
                            });
                        }
                    }, {
                        text: this.LANG.manager.cancel,
                        func: () => {
                        }
                    }]);
            },
            addRoute() {
                this._loading = true;
                this.$http.post(this.URL.API + 'router/add', {alias: this.newAlias}).then((json) => {
                    this._loading = false;

                    if (json.data.status) {
                        this._notify(this.LANG.manager.successful, this.LANG.setting.router.added_new_route_successfully, 'success');
                        this.routes[this.newAlias] = {
                            package: '',
                            is_lock: false,
                        };
                        this.newAlias = null;
                        this.isAdd = false;
                    } else
                        this._notify(this.LANG.manager.error, json.data.result, 'danger');
                });
            },
            getRoutes() {
                this.isLoading = true;
                this.$http.get(this.URL.API + 'router/get').then((json) => {
                    this.isLoading = false;
                    this.routes = json.data;
                });
            },
            chooseApp(app, aliasName) {
                if (app.is_lock) return;
                this.isChooseApp = true;
                this.selectedAlias = aliasName;
            },
            setPackageName(packageName) {
                this._loading = true;
                this.$http.post(this.URL.API + 'router/setPackageName', {
                    alias: this.selectedAlias,
                    packageName: packageName
                }).then((json) => {
                    this._loading = false;
                    if (json.data.status) {
                        if (this.apps[packageName] !== undefined) {
                            let app = this.apps[packageName];
                            this.routes[this.selectedAlias] = {
                                name: app.name,
                                description: app.description,
                                version: app.version,
                                developer: app.developer,
                                icon: app.icon,
                                package: app.package_name,
                                is_lock: false
                            };
                            this.getApps();
                        } else {
                            this.getRoutes();
                        }
                        this.selectedAlias = null;
                        this.isChooseApp = false;
                    }
                });
            }

        },
        created() {
            this.getRoutes();

        },
        updated() {
            //new SimpleBar(document.getElementById('content'));
        }
    }
</script>