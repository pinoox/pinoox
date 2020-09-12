<template>
    <section>
        <div id="workspace">
            <Widgets></Widgets>
        </div>

        <div id="pin-dock">
            <div class="handler">
                <i class="fas fa-expand-arrows-alt"></i>
            </div>
            <div class="apps">
                <div class="apps-holder pretty-scroll">
                    <div v-for="(app,key) in apps" class="app-item" @click="openApp(app)" v-if="!app.hidden">
                        <img :src="app.icon" class="app-icon">
                        <span class="app-name">{{app.name}}</span>
                    </div>
                </div>
            </div>
        </div>

    </section>
</template>

<script>
    import Widgets from './widgets.vue';
    import {mapState, mapMutations} from 'vuex';

    export default {
        created(){
            this.pushToTabs({key:'home'});
        },
        data() {
            return {
                app: null,
            }
        },
        components: {Widgets},
        computed: {
            ...mapState(['apps', 'user']),
        },
        methods: {
            openApp(app) {
                this.$router.replace({name: app.open,params:{package_name:app.package_name}});
            },
        },
    };
</script>