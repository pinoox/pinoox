<template>
    <div class="install-step">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div id="page">
                    <header class="page-header">
                        <h1 class="title">{{ userLang.info_admin }}</h1>
                        <p class="description">{{ userLang.info_admin_description }}</p>
                    </header>
                    <div class="page-panel">
                        <div v-if="isErr" class="page-alert page-alert--error" role="alert">
                            <Icon name="times"/>
                            <span>{{ err }}</span>
                        </div>
                        <div class="form form--scroll">
                            <div @keypress.enter="next()">
                                <div class="install-field">
                                    <label for="user-fname">{{ userLang.name }}</label>
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
                                    <label for="user-lname">{{ userLang.family_name }}</label>
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
                                    <label for="user-email">{{ userLang.email }}</label>
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
                                    <label for="user-username">{{ userLang.username }}</label>
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
                                    <label for="user-password">{{ userLang.password }}</label>
                                    <PasswordInput
                                        id="user-password"
                                        v-model="user.password"
                                        name="password"
                                        placeholder="password"
                                        autocomplete="new-password"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="page-actions">
                        <button type="button" class="btn btn-outline-light pin-btn" @click="prev()">
                            {{ install.back }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-light pin-btn"
                            :disabled="isLoading"
                            @click="next()"
                        >
                            {{ install.setup }}
                        </button>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <InstallProgressModal
            v-model:open="progressOpen"
            :done="installDone"
            @complete="onInstallComplete"
        />
    </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue'
import {useRouter} from 'vue-router'
import {storeToRefs} from 'pinia'
import {installAPI} from '@api/install.js'
import {readApiErrorMessage} from '@/utils/apiEnvelope.js'
import {useInstallStore} from '@/stores/install.js'
import Icon from '@/components/icons/Icon.vue'
import PasswordInput from '@/components/PasswordInput.vue'
import InstallProgressModal from '@/components/InstallProgressModal.vue'
import {useInstallerLang} from '@/composables/useInstallerLang.js'
import {useInstaller} from '@/composables/useInstaller.js'
import {getUrl} from '@/boot.js'

defineProps({
    steps: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:steps'])

const router = useRouter()
const store = useInstallStore()
const {db, user} = storeToRefs(store)
const {install, user: userLang} = useInstallerLang()
const {redirect} = useInstaller()

const isLoading = ref(false)
const progressOpen = ref(false)
const installDone = ref(false)
const err = ref(null)

const isErr = computed(() => err.value !== null && !!err.value)

onMounted(() => {
    emit('update:steps', [true, true, true])
})

function next() {
    if (isLoading.value) {
        return
    }

    isLoading.value = true
    installDone.value = false
    progressOpen.value = true
    err.value = null

    installAPI.setup({
        db: db.value,
        user: user.value,
    }).then(() => {
        installDone.value = true
    }).catch((error) => {
        progressOpen.value = false
        isLoading.value = false
        installDone.value = false
        err.value = readApiErrorMessage(error, install.value?.err_insert_tables)
    })
}

function onInstallComplete() {
    progressOpen.value = false
    isLoading.value = false
    installDone.value = false
    redirect(getUrl().SITE, 0)
}

function prev() {
    progressOpen.value = false
    isLoading.value = false
    installDone.value = false
    store.isLoading = false
    router.replace({name: 'db'})
}
</script>
