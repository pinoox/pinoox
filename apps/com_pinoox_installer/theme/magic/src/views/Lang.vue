<template>
    <div id="page">
        <header class="page-header">
            <h1 class="title">{{ LANG.install.select_lang }}</h1>
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
                    {{ LANG.install.continue }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import {storeToRefs} from 'pinia'
import {useRouter} from 'vue-router'
import {installAPI} from '@api/install.js'
import {useInstallStore} from '@/stores/install.js'
import {shouldShowBootstrapError} from '@/utils/resolveInstallerApi.js'

const router = useRouter()
const store = useInstallStore()
const {LANG, OPTIONS} = storeToRefs(store)

const items = [
    {label: 'English - EN', lang: 'en', icon: 'flag-icon-gb'},
    {label: 'Persian - IR', lang: 'fa', icon: 'flag-icon-ir'},
]

async function selectLang(selectedLang) {
    try {
        const data = await installAPI.changeLang(selectedLang)
        const translations = data.lang ?? data
        store.setLang(translations, selectedLang, data.direction)
    } catch {
        store.OPTIONS = {...store.OPTIONS, lang: selectedLang}
    }
}

function getLabel(item) {
    return LANG.value?.language?.[item.lang] ?? item.label
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
