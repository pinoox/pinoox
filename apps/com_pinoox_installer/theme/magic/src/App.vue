<template>
    <div class="art-cloud">
        <div class="moving-clouds"></div>
        <div class="steps" v-if="$route.name !== 'lang' && $route.name !== 'setup' && $route.name !== 'bootstrap'">
            <ul>
                <li class="done" v-show="false"></li>
                <li :class="steps[0] ? 'done' : ''"><span> {{ LANG?.install?.agreement }}</span></li>
                <li :class="steps[1] ? 'done' : ''"><span> {{ LANG?.install?.prerequisites }}</span></li>
                <li :class="steps[2] ? 'done' : ''"><span> {{ LANG?.install?.db_info }}</span></li>
                <li :class="steps[3] ? 'done' : ''"><span> {{ LANG?.user?.info_admin }}</span></li>
            </ul>
        </div>
        <router-view v-slot="{ Component }">
            <transition
                mode="out-in"
                enter-active-class="animate__animated animate__fadeIn animate__faster"
                leave-active-class="animate__animated animate__fadeOut animate__faster"
            >
                <component :is="Component" v-model:steps="steps" />
            </transition>
        </router-view>

        <div class="loading" v-if="isLoading || preflightLoading">
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
                    <a target="_blank" href="https://pinoox.com"><img src="@/assets/images/pin-icon.png" alt="pinoox"></a>
                    <a target="_blank" :href="'https://instagram.com/pinoox_' + (OPTIONS.lang === 'fa' ? 'fa' : 'en')"><Icon name="instagram"/></a>
                    <a target="_blank" href="https://twitter.com/pinoox_fa"><Icon name="twitter"/></a>
                    <a target="_blank" href="https://t.me/pinoox"><Icon name="telegram"/></a>
                    <a target="_blank" href="https://github.com/pinoox"><Icon name="github"/></a>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue'
import {useRoute, useRouter} from 'vue-router'
import {storeToRefs} from 'pinia'
import {useInstallStore} from '@/stores/install.js'
import Icon from '@/components/icons/Icon.vue'

const route = useRoute()
const router = useRouter()
const store = useInstallStore()
const {LANG, OPTIONS, isLoading, preflightLoading} = storeToRefs(store)
const steps = ref([])

onMounted(() => {
    if (route.name === undefined || route.name === null) {
        router.replace({name: 'lang'})
    }
})
</script>
