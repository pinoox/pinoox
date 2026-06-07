<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page">
                    <header class="page-header">
                        <h1 class="title">{{ LANG.install.db_info }}</h1>
                        <p class="description">{{ LANG.install.db_info_description }}</p>
                    </header>
                    <div class="page-panel">
                        <div v-if="isErr" class="page-alert page-alert--error" role="alert">
                            <Icon name="times"/>
                            <span>{{ err }}</span>
                        </div>
                        <div class="form" data-simplebar data-simplebar-auto-hide="false">
                            <div @keypress.enter="next()">
                                <div class="install-field">
                                    <label for="db-host">{{ LANG.install.db_host }}</label>
                                    <input
                                        id="db-host"
                                        v-model="params.host"
                                        type="text"
                                        name="host"
                                        class="pin-input form-control ltr"
                                        placeholder="localhost"
                                        autocomplete="off"
                                    >
                                </div>
                                <div class="install-field">
                                    <label for="db-name">{{ LANG.install.db_name }}</label>
                                    <input
                                        id="db-name"
                                        v-model="params.database"
                                        type="text"
                                        name="database"
                                        class="pin-input form-control ltr"
                                        placeholder="database"
                                        autocomplete="off"
                                    >
                                </div>
                                <div class="install-field">
                                    <label for="db-user">{{ LANG.install.db_username }}</label>
                                    <input
                                        id="db-user"
                                        v-model="params.username"
                                        type="text"
                                        name="username"
                                        class="pin-input form-control ltr"
                                        placeholder="username"
                                        autocomplete="off"
                                    >
                                </div>
                                <div class="install-field">
                                    <label for="db-pass">{{ LANG.install.db_password }}</label>
                                    <PasswordInput
                                        id="db-pass"
                                        v-model="params.password"
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
                            {{ LANG.install.back }}
                        </button>
                        <button
                            v-if="!isLoading"
                            type="button"
                            class="btn btn-light pin-btn"
                            @click="next()"
                        >
                            {{ LANG.install.continue }}
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
import {readApiErrorMessage} from '@/utils/apiEnvelope.js'
import {useInstallStore} from '@/stores/install.js'
import Icon from '@/components/icons/Icon.vue'
import PasswordInput from '@/components/PasswordInput.vue'

defineProps({
    steps: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:steps'])

const router = useRouter()
const store = useInstallStore()
const {LANG, db} = storeToRefs(store)

const isLoading = ref(false)
const err = ref(null)

const isErr = computed(() => err.value !== null && !!err.value)

const params = computed({
    get: () => db.value,
    set: (val) => {
        db.value = val
    },
})

onMounted(() => {
    emit('update:steps', [true, true])
})

function next() {
    isLoading.value = true
    err.value = null
    installAPI.checkDB(params.value).then(() => {
        isLoading.value = false
        router.replace({name: 'user'})
    }).catch((error) => {
        isLoading.value = false
        err.value = readApiErrorMessage(error, LANG.value?.install?.err_connect_to_database)
    })
}

function prev() {
    router.replace({name: 'prerequisites'})
}
</script>
