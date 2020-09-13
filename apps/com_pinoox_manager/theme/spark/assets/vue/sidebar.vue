<template>
    <div>
        <div v-if="!!title" class="sidebar-title">
            <span class="name">{{title}}</span>
        </div>
        <router-link v-if="!!back" class="item back"
                     :to="{name:typeof _sidebar.back === 'string'? _sidebar.back : 'setting-dashboard'}">
            <i class="fas fa-chevron-right"></i>&nbsp;
            <span class="name"> {{LANG.manager.back}}</span>
        </router-link>
        <div v-if="!!app || topList.length >0" class="app-info">
            <img v-if="!!app" :src="app.icon" class="icon">
            <span v-if="!!app" class="name">{{app.name}}</span>
            <div v-if="topList.length >0" class="text">
                <router-link v-for="topItem in topList"
                             :to="{name:topItem.name,params:!!topItem.params? topItem.params : {}}"
                             class="btn" :title="LANG.manager[topItem.title]"><i
                        :class="topItem.icon"></i></router-link>
            </div>
        </div>
        <router-link v-for="(menu,index) in menus" exact-active-class="active"
                     class="item" :class="!!menu.class? menu.class : ''"
                     :to="{name:menu.name, params: !!menu.params? menu.params : {}}">
            <img v-if="menu.img!=null" :src="menu.img">
            <i v-else class="fas" :class="menu.icon"></i>
            &nbsp;<span class="name">{{LANG.manager[menu.label]}}</span>
            <div class="notify-effect" v-if="notifyInstaller && menu.name === 'app-home'">
                <div class="double-bounce1"></div>
                <div class="double-bounce2"></div>
            </div>
            <div v-if="!!menu.left && (typeof menu.left.isLogin !== 'boolean' || (!menu.left.isLogin && !pinooxAuth.isLogin)  || (menu.left.isLogin && pinooxAuth.isLogin))">
                <i :class="menu.left.icon"></i>
            </div>
        </router-link>
    </div>
</template>

<script>
    export default {
        computed: {
            back() {
                return !!this._sidebar.back ? this._sidebar.back : false;
            },
            title() {
                return !!this._sidebar.title ? this._sidebar.title : false;
            },
            topList() {
                return !!this._sidebar.topList && this._sidebar.topList.length > 0? this._sidebar.topList : [];
            },
            menus() {
                return !!this._sidebar.menus && this._sidebar.menus.length > 0? this._sidebar.menus : [];
            },
            app() {
                return !!this._sidebar.app ? this.apps[this._sidebar.app] : false;
            },
            apps: {
                get() {
                    return this.$store.state.apps;
                },
                set(val) {
                    this.$store.state.apps = val;
                }
            },
            pinooxAuth: {
                get() {
                    return this.$store.state.pinooxAuth;
                }
            },
            notifyInstaller: {
                get() {
                    return this.$store.state.readyInstallCount;
                }
            },
        },
    }
</script>
