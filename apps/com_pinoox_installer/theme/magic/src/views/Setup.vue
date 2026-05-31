<template>
    <div id="page">
        <div class="text-center animate__animated animate__zoomInDown">
            <h1 class="title">{{ LANG.install.main_title }}</h1>
            <h2 class="description">{{ LANG.install.main_description }}</h2>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <router-link :to="{ name: 'rules' }" custom v-slot="{ navigate }">
                        <span class="app animate__animated animate__bounceInDown" @click="navigate">
                            <img class="icon" src="@/assets/images/logo/logo-256.png" alt="pinoox">
                            <span class="app-name">{{ LANG.install.install_pinoox }}</span>
                            <span class="app-version">{{ LANG.install.version }} <b>{{ OPTIONS.version }}</b></span>
                        </span>
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {storeToRefs} from 'pinia'
import {useInstallStore} from '@/stores/install.js'

const store = useInstallStore()
const {LANG, OPTIONS} = storeToRefs(store)
</script>
