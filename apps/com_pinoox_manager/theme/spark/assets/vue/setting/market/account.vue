<template>
    <div class="market-login">
        <router-link :to="{name:'setting-market'}" class="return pin-btn"><i
                class="fa fa-chevron-right"></i> {{LANG.manager.return}}
        </router-link>
        <br>
        <br>

        <div class="user">
            <img v-if="pinooxAuth.avatar_thumb!=null" class="user-image" :src="pinooxAuth.avatar_thumb" alt="user-profile-image">
            <h4>{{pinooxAuth.fullname}}</h4>
            <h5>{{pinooxAuth.email}}</h5>

            <a @click="logoutPinooxAuth()" class="logout" href="">{{LANG.user.logout_account}}</a>

        </div>

    </div>
</template>
<script>
    import {mapMutations} from 'vuex';

    export default {
        computed: {
            pinooxAuth: {
                get() {
                    return this.$store.state.pinooxAuth;
                }
            },
        },
        methods:{
            ...mapMutations(['logoutPinooxAuth']),
        },
        created() {
        }

    }
</script>