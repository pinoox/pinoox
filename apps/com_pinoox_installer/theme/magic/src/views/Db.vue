<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page">
                    <h1 class="title">{{ LANG.install.db_info }}</h1>
                    <h2 class="description">{{ LANG.install.db_info_description }}</h2>
                    <div class="box">
                        <div v-if="isErr" class="row col-sm-12">
                            <span class="badge badge-danger mt-2 mb-4">{{ LANG.install.err_connect_to_database }}</span>
                        </div>
                        <div class="form" data-simplebar data-simplebar-auto-hide="false">
                            <div class="container" @keypress.enter="next()">
                                <div class="row form-group">
                                    <label class="col-sm-3">{{ LANG.install.db_host }}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="params.host" type="text" name="host"
                                               class="pin-input form-control ltr"
                                               placeholder="localhost">
                                    </div>
                                </div>

                                <div class="row form-group">
                                    <label class="col-sm-3">{{ LANG.install.db_name }}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="params.database" type="text" name="database"
                                               class="pin-input form-control ltr" placeholder="database">
                                    </div>
                                </div>

                                <div class="row form-group">
                                    <label class="col-sm-3">{{ LANG.install.db_username }}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="params.username" type="text" name="username"
                                               class="pin-input form-control ltr"
                                               placeholder="username">
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <label class="col-sm-3">{{ LANG.install.db_password }}</label>
                                    <div class="col-sm-6 offset-sm-3">
                                        <input v-model="params.password" type="text" name="password"
                                               class="pin-input form-control ltr" placeholder="password">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="page-actions">
                        <span @click="prev()" class="btn btn-outline-light pin-btn">{{ LANG.install.back }}</span>
                        <span v-if="!isLoading" @click="next()" class="btn btn-light pin-btn">{{ LANG.install.continue }}</span>
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
const isErr = ref(false)

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
    installAPI.checkDB(params.value).then((data) => {
        isLoading.value = false
        if (data.status) {
            router.replace({name: 'user'})
        } else {
            isErr.value = true
        }
    }).catch(() => {
        isLoading.value = false
    })
}

function prev() {
    router.replace({name: 'prerequisites'})
}
</script>
