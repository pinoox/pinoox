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
            <div class="list">
                <div v-if="activeNotifications">
                    <div class="item" @click="routeNotification(index,item)"
                         v-for="(item,index) in notifications.setting">
                        <div v-if="!!item.title" class="title">{{item.title}}</div>
                        <div v-if="!!item.message" class="message">{{item.message}}</div>
                        <vm-progress class="percent" v-if="!!item.percent" :percentage="item.percent" :text-inside="true" :stroke-width="15" :striped="true"></vm-progress>
                    </div>
                    <div class="item" @click="hideNotification(index,item)"
                         v-for="(item,index) in notifications.db">
                        <div class="title">{{item.title}}</div>
                        <div class="message">{{item.message}}</div>
                    </div>
                </div>
                <div v-else>
                    <div class="no-notification">{{LANG.manager.no_new_notification}}</div>
                </div>
            </div>


        </div>

    </div>
</template>

<script>
    import {mapGetters, mapMutations, mapState} from 'vuex';

    export default {
        computed: {
            ...mapState(['animDirection']),
            ...mapGetters(['background', 'isBackground', 'isOpenNotification', 'hasNotification']),
            notifications: {
                get() {
                    return this.$store.state.notifications;
                },
                set(val) {
                    this.$store.state.notifications = val;
                }
            },
            activeNotifications() {
                return this.notifications.setting.length + this.notifications.db.length > 0
            },
            getIds() {
                return this.notifications.db.map((row) => {
                    return row['ntf_id'];
                });
            }
        },
        methods: {
            ...mapMutations(['logout', 'lock', 'toggleNotification']),
            seenNotification: function () {
                if (!this.hasNotification)
                    return;

                // setting notifications
                this.notifications.setting.forEach(notification=>{
                    notification.status = 'seen';
                });

                // db notification
                this.$http.post(PINOOX.URL.API + 'notification/seen', {notifications: this.getIds}).then((json) => {
                    this.notifications.db.forEach(notification=>{
                        notification.status = 'seen';
                    });
                });
            },
            hideNotification: function (index, item) {
                this.$http.post(PINOOX.URL.API + 'notification/hide', {ntf_id: item.ntf_id}).then((json) => {
                    if (json.data.status) {
                        this.notifications.db.splice(index, 1);
                    }
                });
            },
            routeNotification: function (index, item) {
                if (!item.route)
                    this.notifications.setting.splice(index, 1);
                else
                    this.$router.replace(item.route).catch(error => {});
            },
            actionNotification: function (action) {
                this.toggleNotification();
                if (typeof action == 'string' || action instanceof String)
                    this.$router.replace({name: action});
            },
        },
        watch: {
            isOpenNotification(status) {
                if (status) {
                    this.seenNotification();
                }
            },
        }

    };
</script>