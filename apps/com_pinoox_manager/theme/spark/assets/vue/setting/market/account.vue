<template>
    <div class="market-login">
        <router-link :to="{name:'setting-market'}" class="return pin-btn"><i
                class="fa fa-chevron-right"></i> {{LANG.manager.return}}
        </router-link>
        <br>
        <br>

        <div class="user">
            <img v-if="user!=null" class="user-image" :src="user.avatar_thumb" alt="user-profile-image">
            <h4>{{user.fullname}}</h4>
            <h5>{{user.email}}</h5>

            <a @click="logoutPinooxAuth()" class="logout" href="">{{LANG.user.logout}}</a>

        </div>

    </div>
</template>
<script>
    import {mapGetters,mapMutations} from 'vuex';

    export default {
        computed: {
            ...mapGetters(['pinooxAuth']),
            user: {
                get() {
                    return this.pinooxAuth;
                }
            }
        },
        methods:{
            ...mapMutations(['logoutPinooxAuth']),
        }

    }
</script>