<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page">
                    <header class="page-header">
                        <h1 class="title">{{ install.db_info }}</h1>
                        <p class="description">{{ install.db_info_description }}</p>
                    </header>
                    <div class="page-panel">
                        <div v-if="isSuccess" class="page-alert page-alert--success" role="status">
                            <Icon name="check-circle"/>
                            <span>{{ successMsg }}</span>
                        </div>
                        <div v-if="isErr" class="page-alert page-alert--error" role="alert">
                            <Icon name="times"/>
                            <span>{{ err }}</span>
                        </div>
                        <div class="form form--scroll">
                            <div @keypress.enter="next()">
                                <div class="install-field">
                                    <label for="db-host">{{ install.db_host }}</label>
                                    <input
                                        id="db-host"
                                        v-model="params.host"
                                        type="text"
                                        name="host"
                                        class="pin-input form-control ltr"
                                        placeholder="localhost"
                                        autocomplete="off"
                                        @input="clearFeedback"
                                    >
                                </div>
                                <div class="install-field">
                                    <label for="db-name">{{ install.db_name }}</label>
                                    <input
                                        id="db-name"
                                        v-model="params.database"
                                        type="text"
                                        name="database"
                                        class="pin-input form-control ltr"
                                        placeholder="database"
                                        autocomplete="off"
                                        @input="clearFeedback"
                                    >
                                </div>
                                <div class="install-field">
                                    <label for="db-user">{{ install.db_username }}</label>
                                    <input
                                        id="db-user"
                                        v-model="params.username"
                                        type="text"
                                        name="username"
                                        class="pin-input form-control ltr"
                                        placeholder="username"
                                        autocomplete="off"
                                        @input="clearFeedback"
                                    >
                                </div>
                                <div class="install-field">
                                    <label for="db-pass">{{ install.db_password }}</label>
                                    <PasswordInput
                                        id="db-pass"
                                        v-model="params.password"
                                        name="password"
                                        placeholder="password"
                                        autocomplete="new-password"
                                        @update:model-value="clearFeedback"
                                    />
                                </div>

                                <div class="db-advanced">
                                    <button
                                        type="button"
                                        class="db-advanced__toggle"
                                        :aria-expanded="showAdvanced ? 'true' : 'false'"
                                        @click="showAdvanced = !showAdvanced"
                                    >
                                        <Icon name="database"/>
                                        <span>{{ advancedToggleLabel }}</span>
                                    </button>

                                    <div v-show="showAdvanced" class="db-advanced__panel">
                                        <div class="install-field">
                                            <label for="db-connection">{{ install.db_connection }}</label>
                                            <select
                                                id="db-connection"
                                                v-model="params.connection"
                                                name="connection"
                                                class="pin-input form-control"
                                                @change="onConnectionChange"
                                            >
                                                <option
                                                    v-for="option in connectionOptions"
                                                    :key="option.value"
                                                    :value="option.value"
                                                    :disabled="option.disabled"
                                                >
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                            <p
                                                v-if="connectionHint"
                                                class="install-field__hint"
                                            >
                                                {{ connectionHint }}
                                            </p>
                                        </div>
                                        <div class="install-field">
                                            <label for="db-port">{{ install.db_port }}</label>
                                            <input
                                                id="db-port"
                                                v-model="params.port"
                                                type="text"
                                                name="port"
                                                class="pin-input form-control ltr"
                                                placeholder="3306"
                                                inputmode="numeric"
                                                autocomplete="off"
                                                @input="clearFeedback"
                                            >
                                        </div>
                                        <div class="install-field">
                                            <label for="db-prefix">{{ install.db_prefix }}</label>
                                            <input
                                                id="db-prefix"
                                                v-model="params.prefix"
                                                type="text"
                                                name="prefix"
                                                class="pin-input form-control ltr"
                                                placeholder="pinx_"
                                                autocomplete="off"
                                                @input="clearFeedback"
                                            >
                                        </div>
                                        <div class="install-field">
                                            <label for="db-timezone">{{ install.db_timezone }}</label>
                                            <input
                                                id="db-timezone"
                                                v-model="params.timezone"
                                                type="text"
                                                name="timezone"
                                                class="pin-input form-control ltr"
                                                placeholder="+03:30"
                                                autocomplete="off"
                                                @input="clearFeedback"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="page-actions">
                        <button type="button" class="btn btn-outline-light pin-btn" @click="prev()">
                            {{ install.back }}
                        </button>
                        <button
                            v-if="!isTesting"
                            type="button"
                            class="btn btn-outline-light pin-btn"
                            :disabled="isLoading"
                            @click="testConnection()"
                        >
                            <Icon name="database"/>
                            {{ install.db_test }}
                        </button>
                        <span
                            v-else
                            class="btn btn-outline-light pin-btn pin-loading"
                            aria-busy="true"
                        >
                            <Icon name="spinner" spin/>
                        </span>
                        <button
                            v-if="!isLoading"
                            type="button"
                            class="btn btn-light pin-btn"
                            :disabled="isTesting"
                            @click="next()"
                        >
                            {{ install.continue }}
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
import {computed, onMounted, ref, watch} from 'vue'
import {useRouter} from 'vue-router'
import {storeToRefs} from 'pinia'
import {installAPI} from '@api/install.js'
import {readApiErrorMessage} from '@/utils/apiEnvelope.js'
import {useInstallStore} from '@/stores/install.js'
import {useInstallerLang} from '@/composables/useInstallerLang.js'
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
const {db, availableDbConnections} = storeToRefs(store)
const {install} = useInstallerLang()

const connectionCatalog = [
    {value: 'mysql', labelKey: 'db_connection_mysql'},
    {value: 'mariadb', labelKey: 'db_connection_mariadb'},
    {value: 'pgsql', labelKey: 'db_connection_pgsql'},
    {value: 'sqlsrv', labelKey: 'db_connection_sqlsrv'},
]

const isLoading = ref(false)
const isTesting = ref(false)
const err = ref(null)
const successMsg = ref(null)
const showAdvanced = ref(false)

const isErr = computed(() => err.value !== null && !!err.value)
const isSuccess = computed(() => successMsg.value !== null && !!successMsg.value)

const advancedToggleLabel = computed(() => (
    showAdvanced.value
        ? install.value?.db_advanced_hide
        : install.value?.db_advanced_show
) ?? (showAdvanced.value ? 'Hide advanced options' : 'Show advanced options'))

const params = computed({
    get: () => db.value,
    set: (val) => {
        db.value = val
    },
})

const defaultPorts = {
    mysql: '3306',
    mariadb: '3306',
    pgsql: '5432',
    sqlsrv: '1433',
}

const connectionOptions = computed(() => {
    const labels = install.value ?? {}
    const available = availableDbConnections.value ?? []
    const hasAvailability = available.length > 0

    return connectionCatalog.map((entry) => ({
        value: entry.value,
        label: labels[entry.labelKey] ?? entry.value,
        disabled: hasAvailability && !available.includes(entry.value),
    }))
})

const connectionHint = computed(() => {
    const available = availableDbConnections.value ?? []

    if (available.length === 0) {
        return install.value?.db_connection_prerequisites_pending ?? null
    }

    const labels = connectionOptions.value
        .filter((option) => available.includes(option.value))
        .map((option) => option.label)

    if (labels.length === 0) {
        return null
    }

    const template = install.value?.db_connection_available_hint ?? 'Available on this server: :list'

    return template.replace(':list', labels.join(', '))
})

function ensureValidConnection() {
    const available = availableDbConnections.value ?? []

    if (available.length === 0) {
        if (!db.value.connection) {
            db.value.connection = 'mysql'
        }

        return
    }

    if (!available.includes(db.value.connection)) {
        db.value.connection = available.includes('mysql') ? 'mysql' : available[0]
        db.value.port = defaultPorts[db.value.connection] ?? '3306'
    }
}

onMounted(() => {
    emit('update:steps', [true, true])

    ensureValidConnection()

    if (!db.value.port) {
        db.value.port = defaultPorts[db.value.connection] ?? '3306'
    }

    if (!db.value.timezone) {
        db.value.timezone = '+03:30'
    }
})

function onConnectionChange() {
    clearFeedback()
    db.value.port = defaultPorts[db.value.connection] ?? '3306'
}

watch(() => db.value.connection, (connection) => {
    if (!connection) {
        return
    }

    const expected = defaultPorts[connection]
    const current = db.value.port
    const knownDefaults = Object.values(defaultPorts)

    if (!current || knownDefaults.includes(current)) {
        db.value.port = expected
    }
})

function clearFeedback() {
    err.value = null
    successMsg.value = null
}

function testConnection() {
    isTesting.value = true
    clearFeedback()

    installAPI.checkDB(params.value).then(() => {
        isTesting.value = false
        successMsg.value = install.value?.connect_to_database ?? 'Database connection successful.'
    }).catch((error) => {
        isTesting.value = false
        err.value = readApiErrorMessage(error, install.value?.err_connect_to_database)
    })
}

function next() {
    isLoading.value = true
    clearFeedback()

    installAPI.checkDB(params.value).then(() => {
        isLoading.value = false
        router.replace({name: 'user'})
    }).catch((error) => {
        isLoading.value = false
        err.value = readApiErrorMessage(error, install.value?.err_connect_to_database)
    })
}

function prev() {
    router.replace({name: 'prerequisites'})
}
</script>
