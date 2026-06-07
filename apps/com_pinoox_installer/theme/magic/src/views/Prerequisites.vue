<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page" class="prerequisites-page">
                    <header class="prerequisites-header page-header">
                        <h1 class="title">{{ LANG.install.prerequisites }}</h1>
                        <p class="description">{{ LANG.install.prerequisites_description }}</p>
                    </header>

                    <InstallConnectionAlert
                        v-if="connectionError"
                        :error="connectionError"
                        :retry-label="LANG.install.err_connection_retry"
                        @retry="loadPrerequisites"
                    />

                    <div class="prerequisites-panel">
                        <div
                            class="prerequisites-overview"
                            :class="overviewClass"
                        >
                            <div class="prerequisites-overview__progress" aria-hidden="true">
                                <svg viewBox="0 0 44 44">
                                    <circle class="prerequisites-overview__track" cx="22" cy="22" r="18"/>
                                    <circle
                                        class="prerequisites-overview__fill"
                                        cx="22"
                                        cy="22"
                                        r="18"
                                        :style="{ strokeDashoffset: progressOffset }"
                                    />
                                </svg>
                                <span class="prerequisites-overview__count">
                                    <template v-if="isChecking">…</template>
                                    <template v-else>{{ stats.pass + stats.unknown }}/{{ stats.total }}</template>
                                </span>
                            </div>
                            <div class="prerequisites-overview__text">
                                <strong>{{ overviewTitle }}</strong>
                                <p>{{ overviewSubtitle }}</p>
                            </div>
                            <div v-if="isChecking" class="prerequisites-overview__pulse"/>
                        </div>

                        <div
                            v-if="isError && !connectionError"
                            class="prerequisites-alert"
                        >
                            <Icon name="info"/>
                            <span>{{ LANG.install.err_prerequisites }}</span>
                        </div>

                        <div class="prerequisite-grid">
                            <article
                                v-for="(item, index) in prerequisiteItems"
                                :key="item.key"
                                class="prerequisite-card"
                                :class="cardClass(item.key)"
                                :style="{ '--card-delay': `${index * 0.07}s` }"
                            >
                                <div class="prerequisite-card__accent"/>
                                <div class="prerequisite-card__icon-wrap">
                                    <Icon :name="item.icon" class="prerequisite-card__type-icon"/>
                                    <span class="prerequisite-card__status">
                                        <Icon
                                            :name="getIcon(item.key)"
                                            :spin="isLoading(item.key)"
                                        />
                                    </span>
                                </div>
                                <div class="prerequisite-card__body">
                                    <div class="prerequisite-card__head">
                                        <h3>{{ LANG.install[item.labelKey] }}</h3>
                                        <span class="prerequisite-badge">{{ statusLabel(item.key) }}</span>
                                    </div>

                                    <div class="prerequisite-card__meta">
                                        <div class="prerequisite-card__facts">
                                            <p class="prerequisite-card__current">
                                                <span class="prerequisite-card__current-label">
                                                    {{ currentLabel(item.key) }}
                                                </span>
                                                <strong class="prerequisite-card__current-value">
                                                    {{ currentValue(item.key) }}
                                                </strong>
                                            </p>
                                            <button
                                                v-if="item.key !== 'mod_rewrite'"
                                                type="button"
                                                class="prerequisite-card__tip-btn"
                                                :class="{ 'is-open': openTips[item.key] }"
                                                :aria-expanded="openTips[item.key] ? 'true' : 'false'"
                                                :aria-label="LANG.install.prerequisites_tip_label"
                                                :title="LANG.install.prerequisites_tip_label"
                                                @click="toggleTip(item.key)"
                                            >
                                                <Icon name="help"/>
                                            </button>
                                        </div>
                                        <div
                                            v-show="openTips[item.key]"
                                            class="prerequisite-card__tip-panel"
                                            role="note"
                                        >
                                            <p class="prerequisite-card__tip-text">{{ guideText(item.key) }}</p>
                                        </div>
                                    </div>

                                    <p
                                        v-if="helpText(item.key)"
                                        class="prerequisite-card__help"
                                        :class="{ 'is-warn': item.state === 'unknown' }"
                                    >
                                        {{ helpText(item.key) }}
                                    </p>
                                </div>
                            </article>
                        </div>

                    </div>

                    <div class="page-actions">
                        <button type="button" class="btn btn-outline-light pin-btn" @click="prev()">
                            {{ LANG.install.back }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-light pin-btn"
                            :disabled="!canContinue || isChecking"
                            @click="next()"
                        >
                            {{ LANG.install.continue }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, reactive, ref} from 'vue'
import {useRouter} from 'vue-router'
import {storeToRefs} from 'pinia'
import {installAPI} from '@api/install.js'
import {useInstallStore} from '@/stores/install.js'
import Icon from '@/components/icons/Icon.vue'
import InstallConnectionAlert from '@/components/InstallConnectionAlert.vue'
import {
    diagnoseApiError,
    diagnoseBootstrapError,
    isPinooxLoaded,
} from '@/utils/installDiagnostics.js'

defineProps({
    steps: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:steps'])

const PROGRESS_CIRCUMFERENCE = 2 * Math.PI * 18

const router = useRouter()
const store = useInstallStore()
const {LANG} = storeToRefs(store)

const prerequisiteItems = [
    {key: 'free_space', labelKey: 'prerequisites_required_space', icon: 'hdd'},
    {key: 'php', labelKey: 'prerequisites_php', icon: 'code'},
    {key: 'mod_rewrite', labelKey: 'prerequisites_mod_rewrite', icon: 'link'},
    {key: 'mysql', labelKey: 'prerequisites_mysql', icon: 'database'},
]

const defaultItem = () => ({
    state: 'loading',
    detail: null,
    current: null,
    routingActive: false,
    server: null,
    serverType: null,
    serverDetected: null,
    htaccessRequired: false,
    htaccess: null,
})

const prerequisites = reactive({
    free_space: defaultItem(),
    php: defaultItem(),
    mod_rewrite: defaultItem(),
    mysql: defaultItem(),
})

const connectionError = ref(null)
const isChecking = ref(true)
const openTips = reactive({})

const stats = computed(() => {
    const items = Object.values(prerequisites)

    return {
        pass: items.filter((item) => item.state === 'pass').length,
        fail: items.filter((item) => item.state === 'fail').length,
        unknown: items.filter((item) => item.state === 'unknown').length,
        loading: items.filter((item) => item.state === 'loading').length,
        total: items.length,
    }
})

const progressPercent = computed(() => {
    if (isChecking.value) {
        return 0
    }

    return Math.round(((stats.value.pass + stats.value.unknown) / stats.value.total) * 100)
})

const progressOffset = computed(() =>
    PROGRESS_CIRCUMFERENCE - (progressPercent.value / 100) * PROGRESS_CIRCUMFERENCE
)

const overviewClass = computed(() => {
    if (isChecking.value || connectionError.value) {
        return 'is-checking'
    }

    if (stats.value.fail > 0) {
        return 'is-fail'
    }

    if (canContinue.value && stats.value.unknown > 0) {
        return 'is-pending'
    }

    if (canContinue.value) {
        return 'is-ready'
    }

    return 'is-pending'
})

const overviewTitle = computed(() => {
    const install = LANG.value.install

    if (isChecking.value) {
        return install.prerequisites_overview_checking
    }

    if (connectionError.value || stats.value.fail > 0) {
        return install.prerequisites_overview_issues
    }

    if (stats.value.unknown > 0) {
        return install.prerequisites_overview_manual_check
    }

    return install.prerequisites_overview_ready
})

const overviewSubtitle = computed(() => {
    const install = LANG.value.install

    if (isChecking.value) {
        return install.prerequisites_description
    }

    if (stats.value.fail > 0) {
        return install.err_prerequisites
    }

    if (stats.value.unknown > 0) {
        return install.prerequisites_overview_unknown_rewrite
    }

    return install.prerequisites_description
})

const canContinue = computed(() => {
    if (connectionError.value || isChecking.value) {
        return false
    }

    const items = Object.values(prerequisites)

    if (items.some((item) => item.state === 'loading')) {
        return false
    }

    return items.every((item) => item.state === 'pass' || item.state === 'unknown')
})

const isError = computed(() =>
    Object.values(prerequisites).some((item) => item.state === 'fail')
)

onMounted(() => {
    emit('update:steps', [true])
    loadPrerequisites()
})

function resetItems() {
    for (const item of prerequisiteItems) {
        prerequisites[item.key] = defaultItem()
        openTips[item.key] = false
    }
}

function toggleTip(key) {
    openTips[key] = !openTips[key]
}

function markUnreachableChecks() {
    for (const item of prerequisiteItems) {
        if (item.key === 'mod_rewrite') {
            prerequisites.mod_rewrite = {
                state: 'unknown',
                detail: 'manual_verify',
                current: 'manual_verify',
                routingActive: false,
                server: null,
                serverType: null,
                serverDetected: null,
                htaccessRequired: false,
                htaccess: null,
            }
            continue
        }

        if (prerequisites[item.key].state === 'loading') {
            prerequisites[item.key] = {
                state: 'unknown',
                detail: null,
                current: null,
                routingActive: false,
                server: null,
                serverType: null,
                serverDetected: null,
                htaccessRequired: false,
                htaccess: null,
            }
        }
    }
}

async function verifyApiRouting() {
    try {
        const ping = await installAPI.ping()

        if (!ping?.ok) {
            return
        }

        const rewrite = prerequisites.mod_rewrite
        const install = LANG.value.install

        if (rewrite.state === 'pass') {
            return
        }

        if (rewrite.htaccessRequired && rewrite.htaccess?.ok === false && rewrite.state === 'fail') {
            return
        }

        const apiOk = install.prerequisites_current_api_ok
        const nextCurrent = rewrite.server
            ? `${rewrite.server} — ${apiOk}`
            : apiOk

        if (rewrite.state === 'fail') {
            prerequisites.mod_rewrite = {
                ...rewrite,
                state: 'unknown',
                routingActive: true,
                current: nextCurrent,
            }
            return
        }

        if (rewrite.state === 'unknown') {
            prerequisites.mod_rewrite = {
                ...rewrite,
                routingActive: true,
                current: nextCurrent,
            }
        }
    } catch {
        if (prerequisites.mod_rewrite.state === 'unknown' && !prerequisites.mod_rewrite.routingActive) {
            return
        }
    }
}

async function loadPrerequisites() {
    isChecking.value = true
    connectionError.value = null
    resetItems()

    if (!isPinooxLoaded()) {
        connectionError.value = diagnoseBootstrapError(LANG.value)
        markUnreachableChecks()
        isChecking.value = false
        return
    }

    try {
        const data = await installAPI.checkPrerequisites()
        const items = data.items ?? data

        for (const [type, result] of Object.entries(items)) {
            if (prerequisites[type]) {
                prerequisites[type] = {
                    state: result.state ?? (result.status ? 'pass' : 'fail'),
                    detail: result.detail ?? null,
                    current: result.current ?? result.detail ?? null,
                    routingActive: Boolean(result.routing_active),
                    server: result.server ?? null,
                    serverType: result.server_type ?? null,
                    serverDetected: result.server_detected ?? null,
                    htaccessRequired: Boolean(result.htaccess_required),
                    htaccess: result.htaccess ?? null,
                }
            }
        }

        await verifyApiRouting()
    } catch (error) {
        connectionError.value = diagnoseApiError(error, LANG.value)
        markUnreachableChecks()
    } finally {
        isChecking.value = false
    }
}

function isLoading(type) {
    return prerequisites[type]?.state === 'loading'
}

function cardClass(type) {
    const state = prerequisites[type]?.state

    return {
        'is-loading': state === 'loading',
        'is-pass': state === 'pass',
        'is-fail': state === 'fail',
        'is-unknown': state === 'unknown',
    }
}

function getIcon(type) {
    const state = prerequisites[type]?.state

    if (state === 'loading') return 'spinner'
    if (state === 'fail') return 'times'
    if (state === 'unknown') return 'warning'
    if (state === 'pass') return 'check-circle'

    return 'spinner'
}

function statusLabel(type) {
    const state = prerequisites[type]?.state
    const install = LANG.value.install

    if (state === 'loading') return install.prerequisites_status_checking
    if (state === 'fail') return install.prerequisites_status_fail
    if (state === 'unknown') return install.prerequisites_status_unknown
    if (state === 'pass') return install.prerequisites_status_pass

    return install.prerequisites_status_checking
}

function currentLabel(type) {
    const map = {
        free_space: 'prerequisites_current_space',
        php: 'prerequisites_current_php',
        mod_rewrite: 'prerequisites_current_rewrite',
        mysql: 'prerequisites_current_mysql',
    }

    return LANG.value.install[map[type]] ?? ''
}

function currentValue(type) {
    const item = prerequisites[type]
    const install = LANG.value.install

    if (!item || item.state === 'loading') {
        return install.prerequisites_current_checking
    }

    if (type === 'mod_rewrite') {
        const htaccessLabel = rewriteHtaccessValue(item, install)

        if (htaccessLabel) {
            if (item.server && item.state !== 'pass') {
                return `${item.server} — ${htaccessLabel}`
            }

            return htaccessLabel
        }

        if (item.routingActive && item.state === 'unknown') {
            if (item.server) {
                return `${item.server} — ${install.prerequisites_current_api_ok}`
            }

            return install.prerequisites_current_api_ok
        }

        if (item.state === 'unknown' && (item.current === 'manual_verify' || item.detail === 'manual_verify')) {
            if (item.server) {
                return `${item.server} — ${install.prerequisites_current_manual_verify}`
            }

            return install.prerequisites_current_manual_verify
        }

        if (item.state === 'unknown' && !item.server && item.serverDetected === false) {
            return install.prerequisites_current_server_unknown
        }
    }

    if (type === 'free_space' && item.state === 'unknown') {
        if (item.detail === 'shared_hosting') {
            return install.prerequisites_current_cannot_detect_space
        }
    }

    const raw = item.current ?? item.detail

    if (!raw || raw === 'none') {
        return install.prerequisites_current_none
    }

    if (raw === 'disabled') {
        return install.prerequisites_current_rewrite_off
    }

    if (raw === 'routing active') {
        return install.prerequisites_current_routing_active
    }

    if (type === 'mod_rewrite') {
        const mapped = rewriteDetailLabel(raw, install)

        if (mapped) {
            return mapped
        }
    }

    if (item.state === 'unknown' && type === 'mod_rewrite' && item.detail) {
        return rewriteDetailLabel(item.detail, install) ?? item.detail
    }

    return raw
}

function rewriteDetailLabel(key, install) {
    const map = {
        htaccess_missing: install.prerequisites_current_htaccess_missing,
        htaccess_empty: install.prerequisites_current_htaccess_empty,
        htaccess_no_pinoox: install.prerequisites_current_htaccess_no_pinoox,
        htaccess_ok: install.prerequisites_current_htaccess_ok,
        rewrite_htaccess_ok: install.prerequisites_current_rewrite_htaccess_ok,
        rewrite_htaccess_active: install.prerequisites_current_rewrite_htaccess_active,
    }

    return map[key] ?? null
}

function rewriteHtaccessValue(item, install) {
    if (!item.htaccessRequired || !item.htaccess) {
        return null
    }

    if (item.htaccess.ok) {
        return install.prerequisites_current_htaccess_ok
    }

    return rewriteDetailLabel(item.htaccess.detail, install)
}

function guideText(type) {
    const map = {
        free_space: 'prerequisites_tip_space',
        php: 'prerequisites_tip_php',
        mod_rewrite: 'prerequisites_tip_rewrite',
        mysql: 'prerequisites_tip_mysql',
    }

    return LANG.value.install[map[type]] ?? ''
}

function helpText(type) {
    const item = prerequisites[type]
    const install = LANG.value.install

    if (!item || item.state === 'loading') {
        return ''
    }

    if (type === 'free_space' && item.state === 'unknown') {
        return install.prerequisites_help_space_unknown
    }

    if (type === 'mod_rewrite' && (item.state === 'unknown' || item.state === 'fail')) {
        return install.prerequisites_status_unknown
    }

    if (item.state === 'pass') {
        return ''
    }

    const map = {
        free_space: 'prerequisites_help_space_fail',
        php: 'prerequisites_help_php_fail',
        mysql: 'prerequisites_help_mysql_fail',
    }

    return install[map[type]] ?? ''
}

function next() {
    if (canContinue.value) {
        router.replace({name: 'db'})
    }
}

function prev() {
    router.replace({name: 'rules'})
}
</script>
