<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page">
                    <h1 class="title">{{ LANG.user.info_admin }}</h1>
                    <h2 class="description">{{ LANG.user.info_admin_description }}</h2>
                    <div class="box">
                        <div v-if="isErr" class="row col-sm-12">
                            <span class="badge badge-danger mt-2 mb-4">{{ err }}</span>
                        </div>
                        <div class="form" data-simplebar data-simplebar-auto-hide="false">
                            <div class="container" @keypress.enter="next()">
                                <div class="row form-group">
                                    <label class="col-sm-3">{{ LANG.user.name }}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="user.fname" type="text" name="fname"
                                               class="pin-input form-control ltr"
                                               placeholder="first name">
                                    </div>
                                </div>

                                <div class="row form-group">
                                    <label class="col-sm-3">{{ LANG.user.family_name }}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="user.lname" type="text" name="lname"
                                               class="pin-input form-control ltr"
                                               placeholder="last name">
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <label class="col-sm-3">{{ LANG.user.email }}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="user.email" type="text" name="email"
                                               class="pin-input form-control ltr"
                                               placeholder="email">
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <label class="col-sm-3">{{ LANG.user.username }}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="user.username" type="text" name="username"
                                               class="pin-input form-control ltr"
                                               placeholder="username">
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <label class="col-sm-3">{{ LANG.user.password }}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="user.password" type="text" name="password"
                                               class="pin-input form-control ltr"
                                               placeholder="password">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="page-actions">
                        <span @click="prev()" class="btn btn-outline-light pin-btn">{{ LANG.install.back }}</span>
                        <span v-if="!isLoading" @click="next()" class="btn btn-light pin-btn">{{ LANG.install.setup }}</span>
                        <span v-else class="btn btn-light pin-btn pin-loading"><Icon name="spinner" spin/></span>
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
    installAPI.setup({
        db: db.value,
        user: user.value,
    }).then((data) => {
        const isInstall = !data || typeof data !== 'object' || data.status
        if (isInstall) {
            setTimeout(() => {
                isLoading.value = false
                redirect(PINOOX.URL.SITE, 0)
            }, 3000)
        } else {
            isLoading.value = false
            err.value = data.result
        }
    }).catch(() => {
        isLoading.value = false
    })
}

function prev() {
    router.replace({name: 'db'})
}
</script>
