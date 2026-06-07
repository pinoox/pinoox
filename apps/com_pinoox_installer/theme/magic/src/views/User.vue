<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page">
                    <header class="page-header">
                        <h1 class="title">{{ LANG.user.info_admin }}</h1>
                        <p class="description">{{ LANG.user.info_admin_description }}</p>
                    </header>
                    <div class="page-panel">
                        <div v-if="isErr" class="page-alert page-alert--error" role="alert">
                            <Icon name="times"/>
                            <span>{{ err }}</span>
                        </div>
                        <div class="form" data-simplebar data-simplebar-auto-hide="false">
                            <div @keypress.enter="next()">
                                <div class="install-field">
                                    <label for="user-fname">{{ LANG.user.name }}</label>
                                    <input
                                        id="user-fname"
                                        v-model="user.fname"
                                        type="text"
                                        name="fname"
                                        class="pin-input form-control ltr"
                                        placeholder="first name"
                                        autocomplete="given-name"
                                    >
                                </div>
                                <div class="install-field">
                                    <label for="user-lname">{{ LANG.user.family_name }}</label>
                                    <input
                                        id="user-lname"
                                        v-model="user.lname"
                                        type="text"
                                        name="lname"
                                        class="pin-input form-control ltr"
                                        placeholder="last name"
                                        autocomplete="family-name"
                                    >
                                </div>
                                <div class="install-field">
                                    <label for="user-email">{{ LANG.user.email }}</label>
                                    <input
                                        id="user-email"
                                        v-model="user.email"
                                        type="email"
                                        name="email"
                                        class="pin-input form-control ltr"
                                        placeholder="email"
                                        autocomplete="email"
                                    >
                                </div>
                                <div class="install-field">
                                    <label for="user-username">{{ LANG.user.username }}</label>
                                    <input
                                        id="user-username"
                                        v-model="user.username"
                                        type="text"
                                        name="username"
                                        class="pin-input form-control ltr"
                                        placeholder="username"
                                        autocomplete="username"
                                    >
                                </div>
                                <div class="install-field">
                                    <label for="user-password">{{ LANG.user.password }}</label>
                                    <input
                                        id="user-password"
                                        v-model="user.password"
                                        type="password"
                                        name="password"
                                        class="pin-input form-control ltr"
                                        placeholder="password"
                                        autocomplete="new-password"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="page-actions">
                        <button type="button" class="btn btn-outline-light pin-btn" @click="prev()">
                            {{ LANG.install.back }}
                        </button>
                        <button
                            v-if="!isLoading"
                            type="button"
                            class="btn btn-light pin-btn"
                            @click="next()"
                        >
                            {{ LANG.install.setup }}
                        </button>
                        <span v-else class="btn btn-light pin-btn pin-loading" aria-busy="true">
                            <Icon name="spinner" spin/>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue'
import {useRouter} from 'vue-router'
import {storeToRefs} from 'pinia'
import {installAPI} from '@api/install.js'
import {useInstallStore} from '@/stores/install.js'
import Icon from '@/components/icons/Icon.vue'
import {useInstaller} from '@/composables/useInstaller.js'

defineProps({
    steps: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:steps'])

const router = useRouter()
const store = useInstallStore()
const {LANG, db, user} = storeToRefs(store)
const {redirect} = useInstaller()

const isLoading = ref(false)
const err = ref(null)

const isErr = computed(() => err.value !== null && !!err.value)

onMounted(() => {
    emit('update:steps', [true, true, true])
})

function next() {
    isLoading.value = true
    err.value = null
    installAPI.setup({
        db: db.value,
        user: user.value,
    }).then(() => {
        setTimeout(() => {
            isLoading.value = false
            redirect(PINOOX.URL.SITE, 0)
        }, 3000)
    }).catch((error) => {
        isLoading.value = false
        err.value = error?.message || LANG.value?.install?.err_insert_tables
    })
}

function prev() {
    router.replace({name: 'db'})
}
</script>
