<template>
    <div id="page">
        <header class="page-header animate__animated animate__zoomInDown">
            <h1 class="title">{{ install.main_title }}</h1>
            <p class="description">{{ install.main_description }}</p>
        </header>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <router-link :to="{ name: 'rules' }" custom v-slot="{ navigate }">
                        <span
                            class="app animate__animated animate__bounceInDown"
                            role="button"
                            tabindex="0"
                            @click="navigate"
                            @keydown.enter.prevent="navigate"
                            @keydown.space.prevent="navigate"
                        >
                            <img class="icon" src="@/assets/images/logo/logo-256.png" alt="Pinoox">
                            <span class="app-name">{{ install.install_pinoox }}</span>
                            <span class="app-version">{{ install.version }} <b class="app-version__value" dir="ltr">{{ OPTIONS.version }}</b></span>
                        </span>
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {useInstallerLang} from '@/composables/useInstallerLang.js'

const {install, OPTIONS} = useInstallerLang()
</script>
