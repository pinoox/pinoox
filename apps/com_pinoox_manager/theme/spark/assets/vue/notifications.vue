<template>
    <div id="notifications" class="notifications"
         :class="isOpenNotification ? 'open animated faster fadeIn'+ animDirection : 'close'">
        <div class="blur"><img v-show="isBackground" :src="background" class="cover-background"></div>
        <div class="content" data-simplebar data-simplebar-auto-hide="false">
            <div class="actions">
                <span @click="actionNotification('setting-dashboard')"><i class="fa fa-cog"></i></span>
                <span @click="actionNotification('setting-account')"><i class="fa fa-user"></i></span>
                <span @click="actionNotification(lock())" href="javascript:;"><i class="fa fa-lock"></i></span>
                <span @click="actionNotification(logout())" href="javascript:;"><i
                        class="fas fa-power-off"></i></span>
            </div>
            <div v-if="hasNotification">
                <div class="installation" v-show="installList!==null && installList.length>0">
                    <div class="caption">{{LANG.manager.installation_list}}</div>
                    <div class="item" v-for="app in installList">
                        <img :src="app.icon" alt="" class="icon">
                        <span class="name">{{app.name}}</span>
                        <div class="pin-loader"><i class="fa fa-spinner"></i></div>
                    </div>
                </div>
                <div class="list">
                    <transition-group name="list" tag="div">
                        <div class="item" v-bind:key="item.ntf_id" @click="hideNotification(index,item)"
                             v-for="(item,index) in notifications">
                            <div class="title">{{item.title}}</div>
                            <div class="message">{{item.message}}</div>
                        </div>
                    </transition-group>
                </div>
            </div>
            <div v-else>
                <div class="no-notification">{{LANG.manager.no_new_notification}}</div>
            </div>


        </div>

    </div>
</template>

<script>
    import {mapState, mapGetters, mapMutations} from 'vuex';

    export default {
        name: "notifications",
        computed: {
            ...mapState(['notifications', 'installList', 'animDirection']),
            ...mapGetters(['background', 'isBackground', 'isOpenNotification', 'hasNotification']),
        },
        methods: {
            ...mapMutations(['logout', 'lock', 'toggleNotification']),
            hideNotification: function (index, item) {
                this.$http.post(PINOOX.URL.API + 'notification/hide', {ntf_id: item.ntf_id}).then((json) => {
                    if (json.status) {
                        this.notifications.splice(index, 1);
                    }
                });
            },
            actionNotification: function (action) {
                this.toggleNotification();
                if (typeof action == 'string' || action instanceof String)
                    this.$router.replace({name: action});
            }
        },
        created() {
        }

    };
</script>