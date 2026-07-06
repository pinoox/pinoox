import {createApp, nextTick} from 'vue'
import SimpleBar from 'simplebar'
import 'simplebar/dist/simplebar.css'
import 'flag-icon-css/css/flag-icons.min.css'
import 'animate.css/animate.min.css'
import 'pretty-checkbox/dist/pretty-checkbox.min.css'
import '@/assets/css/fonts.css'
import './assets/scss/main.scss'

import App from './App.vue'
import router from './router'
import pinia from './stores'
import {httpEvent} from '@global'
import {useInstallStore} from '@/stores/install.js'
import {pingInstallerApi} from '@/utils/resolveInstallerApi.js'

function initSimpleBar() {
    nextTick(() => {
        document.querySelectorAll('[data-simplebar]:not([data-simplebar-init])').forEach((el) => {
            new SimpleBar(el)
            el.setAttribute('data-simplebar-init', '')
        })
    })
}

async function boot() {
    const app = createApp(App)

    app.use(pinia)
    app.use(router)

    const store = useInstallStore(pinia)

    store.ensureLang()
    store.syncDirection()

    httpEvent('start', () => {
        store.isLoading = true
    })

    httpEvent('stop', () => {
        store.isLoading = false
    })

    app.mount('#app')

    try {
        const ping = await pingInstallerApi()
        store.setPreflightPing(ping)
    } finally {
        store.preflightLoading = false
    }

    router.isReady().then(initSimpleBar)
    router.afterEach(initSimpleBar)
}

boot()
