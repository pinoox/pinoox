<template>
    <div class="art-cloud">
        <div class="moving-clouds"></div>
        <div class="steps" v-if="$route.name !== 'lang' && $route.name !== 'setup' && $route.name !== 'bootstrap'">
            <ul>
                <li class="done" v-show="false"></li>
                <li :class="stepClass(0)"><span>{{ install.agreement }}</span></li>
                <li :class="stepClass(1)"><span>{{ install.prerequisites }}</span></li>
                <li :class="stepClass(2)"><span>{{ install.db_info }}</span></li>
                <li :class="stepClass(3)"><span>{{ user.info_admin }}</span></li>
            </ul>
        </div>
        <router-view v-slot="{ Component, route: activeRoute }">
            <transition name="route-fade" mode="out-in">
                <component
                    :is="Component"
                    :key="activeRoute.fullPath"
                    v-model:steps="steps"
                />
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
import {computed, onMounted, ref} from 'vue'
import {useRoute, useRouter} from 'vue-router'
import {storeToRefs} from 'pinia'
import {useInstallStore} from '@/stores/install.js'
import {useInstallerLang} from '@/composables/useInstallerLang.js'
import Icon from '@/components/icons/Icon.vue'

const route = useRoute()
const router = useRouter()
const store = useInstallStore()
const {install, user, OPTIONS} = useInstallerLang()
const {isLoading, preflightLoading} = storeToRefs(store)
const steps = ref([])

const stepRoutes = ['rules', 'prerequisites', 'db', 'user']

const currentStepIndex = computed(() => stepRoutes.indexOf(route.name))

function stepClass(index) {
    return {
        done: Boolean(steps.value[index]),
        current: currentStepIndex.value === index,
    }
}

onMounted(() => {
    if (route.name === undefined || route.name === null) {
        router.replace({name: 'lang'})
    }
})
</script>
