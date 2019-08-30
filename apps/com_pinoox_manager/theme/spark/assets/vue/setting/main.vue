<template>
    <section>
    <div id="settings">
        <div id="sidebar" data-simplebar>
            <router-link class="item back" :to="{name:'home'}">
                <i class="fas fa-chevron-right"></i>
                <span class="name">{{LANG.manager.back}}</span>
            </router-link>
            <router-link v-for="menu in menus" exact-active-class="active"  class="item" :to="{name:menu.name}">
                <img v-if="menu.img!=null" :src="menu.img">
                <i v-else class="fas" :class="menu.icon"></i>
                &nbsp;<span class="name">{{LANG.manager[menu.label]}}</span>
            </router-link>
        </div>
        <router-view></router-view>
    </div>
    </section>
</template>

<script>
    export default {
        data()
        {
            return {
                menus : [
                    {
                        name:'setting-dashboard',
                        label: 'interface',
                        icon: 'fa-tv',
                    },
                    {
                        name:'setting-account',
                        label: 'account',
                        icon: 'fa-user',
                    },
                    {
                        name:'setting-router',
                        label: 'router',
                        icon: 'fas fa-code-branch',
                    },
                    {
                        name:'setting-market',
                        label: 'market',
                        img: require('@img/market-icon.png'),
                    },
                    {
                        name:'setting-about',
                        label: 'about',
                        img: require('@img/pin-icon.png'),
                    },
                ]
            }
        },
    }
</script>

