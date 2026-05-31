import {createApp, nextTick} from 'vue'
import SimpleBar from 'simplebar'
import 'simplebar/dist/simplebar.css'
import 'flag-icon-css/css/flag-icons.min.css'
import 'animate.css/animate.min.css'
import 'pretty-checkbox/dist/pretty-checkbox.min.css'
import '@/assets/css/fonts.css'
import './assets/scss/main.scss'

import App from './App.vue'
import BootstrapError from './components/BootstrapError.vue'
import router from './router'
import pinia from './stores'
import {httpEvent} from '@global'
import {useInstallStore} from '@/stores/install.js'

function initSimpleBar() {
    nextTick(() => {
        document.querySelectorAll('[data-simplebar]:not([data-simplebar-init])').forEach((el) => {
            new SimpleBar(el)
            el.setAttribute('data-simplebar-init', '')
        })
    })
}

const app = createApp(typeof PINOOX === 'undefined' ? BootstrapError : App)

app.use(pinia)
app.use(router)

useInstallStore().syncDirection()

httpEvent('start', () => {
    useInstallStore().isLoading = true
})

httpEvent('stop', () => {
    useInstallStore().isLoading = false
})

app.mount('#app')

router.isReady().then(initSimpleBar)
router.afterEach(initSimpleBar)
