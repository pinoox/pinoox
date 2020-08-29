<template>
    <div class="art-cloud">
        <!-- art animation -->
        <div class="moving-clouds"></div>
        <div class="steps" v-show="$route.name!=='lang' && $route.name!=='setup'">
            <ul>
                <li class="done" v-show="false"></li>
                <li :class="steps[0] ? 'done' : ''"><span> {{LANG.install.agreement}}</span></li>
                <li :class="steps[1] ? 'done' : ''"><span> {{LANG.install.prerequisites}}</span></li>
                <li :class="steps[2] ? 'done' : ''"><span> {{LANG.install.db_info}}</span></li>
                <li :class="steps[3] ? 'done' : ''"><span> {{LANG.user.info_admin}}</span></li>
            </ul>
        </div>
        <transition mode="out-in" enter-active-class="animated faster fadeIn"
                    leave-active-class="animated faster fadeOut">
            <router-view :steps.sync="steps"></router-view>
        </transition>

        <div class="loading" v-if="isLoading">
            <div class="lds-roller">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
        <footer class="footer">
            <div class="links">
                <div class="socials">
                    <a target="_blank" href="https://pinoox.com"><img class="" src="@img/pin-icon.png"></a>
                    <a target="_blank" :href="'https://instagram.com/pinoox_'+(OPTIONS.lang === 'fa'? 'fa' : 'en')"><i class="fab fa-instagram"></i></a>
                    <a target="_blank" href="https://twitter.com/pinoox_fa"><i class="fab fa-twitter"></i></a>
                    <a target="_blank" href="https://t.me/pinoox"><i class="fab fa-telegram-plane"></i></a>
                    <a target="_blank" href="https://github.com/pinoox"><i class="fab fa-github"></i></a>
                </div>

            </div>
        </footer>

    </div>
</template>

<script>
    export default {

        created() {
            if (this.$route.name === undefined || this.$route.name === null)
                this.$router.replace({name: 'lang'});
        },
        data() {
            return {
                isLoading: false,
                steps: [],
            }
        },
    }
</script>