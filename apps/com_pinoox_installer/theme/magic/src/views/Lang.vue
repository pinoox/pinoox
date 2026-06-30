<template>
    <div id="page">
        <header class="page-header">
            <h1 class="title">{{ install.select_lang }}</h1>
        </header>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-panel">
                        <ul class="lang">
                            <li v-for="item in items" :key="item.lang">
                                <button
                                    type="button"
                                    class="lang-option"
                                    :class="{ active: item.lang === OPTIONS.lang }"
                                    @click="selectLang(item.lang)"
                                >
                                    <i class="flag-icon" :class="item.icon" aria-hidden="true"></i>
                                    <span>{{ getLabel(item) }}</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="page-actions page-actions--center">
                <button type="button" class="btn btn-light pin-btn pin-next" @click="continueInstall">
                    {{ install.continue }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import {onMounted} from 'vue'
import {useRouter} from 'vue-router'
import {installAPI} from '@api/install.js'
import {getDirection, getLangPack} from '@/lang/index.js'
import {useInstallerLang} from '@/composables/useInstallerLang.js'
import {shouldShowBootstrapError} from '@/utils/resolveInstallerApi.js'

const router = useRouter()
const {install, language, OPTIONS, store} = useInstallerLang()

onMounted(() => {
    store.ensureLang()
})

const items = [
    {label: 'English - EN', lang: 'en', icon: 'flag-icon-gb'},
    {label: 'Persian - IR', lang: 'fa', icon: 'flag-icon-ir'},
]

async function selectLang(selectedLang) {
    try {
        const data = await installAPI.changeLang(selectedLang)
        store.setLang(data, selectedLang, data.direction)
    } catch {
        store.setLang(getLangPack(selectedLang), selectedLang, getDirection(selectedLang))
    }
}

function getLabel(item) {
    return language.value?.[item.lang] ?? item.label
}

function continueInstall() {
    store.refreshBootstrapError()

    if (shouldShowBootstrapError(store.preflightPing)) {
        router.push({name: 'bootstrap', query: {error: ''}})
        return
    }

    router.push({name: 'setup'})
}
</script>
