<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page" class="prerequisites-page">
                    <header class="prerequisites-header">
                        <h1 class="title">{{ LANG.install.prerequisites }}</h1>
                        <h2 class="description">{{ LANG.install.prerequisites_description }}</h2>
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

                                    <p v-if="helpText(item.key)" class="prerequisite-card__help">
                                        {{ helpText(item.key) }}
                                    </p>
                                </div>
                            </article>
                        </div>

                        <details
                            v-if="showRewriteGuide"
                            class="prerequisites-guide"
                        >
                            <summary>
                                <Icon name="info"/>
                                <span>{{ LANG.install.prerequisites_guide_title }}</span>
                            </summary>
                            <div class="prerequisites-guide__grid">
                                <div
                                    v-for="guide in rewriteGuides"
                                    :key="guide.tag"
                                    class="prerequisites-guide__item"
                                >
                                    <span class="prerequisites-guide__tag">{{ guide.tag }}</span>
                                    <p>{{ guide.text }}</p>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div class="page-actions">
                        <span @click="prev()" class="btn btn-outline-light pin-btn">{{ LANG.install.back }}</span>
                        <button
                            @click="next()"
                            class="btn btn-light pin-btn"
                            :disabled="!canContinue || isChecking"
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
    serverDetected: null,
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

const showRewriteGuide = computed(() => {
    if (connectionError.value) {
        return true
    }

    const rewrite = prerequisites.mod_rewrite

    return rewrite.state === 'fail' || rewrite.state === 'unknown'
})

const rewriteGuides = computed(() => {
    const install = LANG.value.install

    return [
        {tag: 'Apache', text: install.prerequisites_guide_apache},
        {tag: 'nginx', text: install.prerequisites_guide_nginx},
        {tag: 'IIS', text: install.prerequisites_guide_iis},
        {tag: 'LiteSpeed', text: install.prerequisites_guide_litespeed},
        {tag: 'Caddy', text: install.prerequisites_guide_caddy},
        {tag: install.prerequisites_status_unknown, text: install.prerequisites_guide_other},
    ]
})

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
            prerequisites.mod_rewrite = {state: 'fail', detail: null, current: 'disabled', routingActive: false, server: null, serverDetected: null}
            continue
        }

        if (prerequisites[item.key].state === 'loading') {
            prerequisites[item.key] = {state: 'unknown', detail: null, current: null, routingActive: false, server: null, serverDetected: null}
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
                    serverDetected: result.server_detected ?? null,
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
    if (state === 'unknown') return 'help'
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
        if (item.routingActive && item.state === 'unknown') {
            if (item.server) {
                return `${item.server} — ${install.prerequisites_current_api_ok}`
            }

            return install.prerequisites_current_api_ok
        }

        if (item.state === 'unknown' && !item.server && item.serverDetected === false) {
            return install.prerequisites_current_server_unknown
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

    if (item.state === 'unknown' && type === 'mod_rewrite' && item.detail) {
        return item.detail
    }

    return raw
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

    if (type === 'mod_rewrite' && item.state === 'unknown') {
        return install.prerequisites_help_rewrite_unknown
    }

    if (item.state === 'pass') {
        return ''
    }

    const map = {
        free_space: 'prerequisites_help_space_fail',
        php: 'prerequisites_help_php_fail',
        mod_rewrite: 'prerequisites_help_rewrite_fail',
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
