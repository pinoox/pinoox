<template>
    <div id="page">
        <div class="text-center">
            <h1 class="title">{{ LANG.install.select_lang }}</h1>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="box">
                        <ul class="lang" data-simplebar data-simplebar-auto-hide="false">
                            <li v-for="item in items" :key="item.lang">
                                <span v-if="item.lang !== OPTIONS.lang" @click="selectLang(item.lang)">
                                    <i class="flag-icon" :class="item.icon"></i> {{ getLabel(item) }}
                                </span>
                                <span v-else class="active">
                                    <i class="flag-icon" :class="item.icon"></i> {{ getLabel(item) }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="page-actions page-actions--center">
                <router-link :to="{ name: 'setup' }" custom v-slot="{ navigate }">
                    <span class="btn btn-light pin-btn pin-next" @click="navigate">{{ LANG.install.continue }}</span>
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import {storeToRefs} from 'pinia'
import {installAPI} from '@api/install.js'
import {useInstallStore} from '@/stores/install.js'

const store = useInstallStore()
const {LANG, OPTIONS} = storeToRefs(store)

const items = [
    {label: 'English - EN', lang: 'en', icon: 'flag-icon-gb'},
    {label: 'Persian - IR', lang: 'fa', icon: 'flag-icon-ir'},
]

function selectLang(selectedLang) {
    installAPI.changeLang(selectedLang).then((data) => {
        const translations = data.lang ?? data
        store.setLang(translations, selectedLang, data.direction)
    })
}

function getLabel(item) {
    return LANG.value?.language?.[item.lang] ?? item.label
}
</script>
