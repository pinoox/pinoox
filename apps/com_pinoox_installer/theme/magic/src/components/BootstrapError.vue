<template>
    <div id="page" class="bootstrap-error">
        <div class="bootstrap-error__wrap">
            <div class="bootstrap-error__card" role="alert">
                <div class="bootstrap-error__brand">
                    <img :src="logoUrl" alt="Pinoox" width="56" height="56">
                </div>

                <div class="bootstrap-error__icon" aria-hidden="true">
                    <Icon name="times"/>
                </div>

                <span class="bootstrap-error__badge">{{ copy.badge }}</span>
                <h1 class="bootstrap-error__title">{{ copy.title }}</h1>
                <p class="bootstrap-error__message">{{ copy.message }}</p>

                <ul class="bootstrap-error__hints">
                    <li
                        v-for="step in steps"
                        :key="step.key"
                        class="bootstrap-error__hint"
                        :class="[
                            `bootstrap-error__hint--${step.state}`,
                            {'bootstrap-error__hint--focus': step.focused},
                            {'bootstrap-error__hint--idle': step.idle},
                        ]"
                    >
                        <span class="bootstrap-error__hint-index">{{ step.number }}</span>
                        <div class="bootstrap-error__hint-body">
                            <span class="bootstrap-error__hint-text">{{ step.text }}</span>
                            <span v-if="step.detail" class="bootstrap-error__hint-detail">{{ step.detail }}</span>
                        </div>
                        <span class="bootstrap-error__hint-status" :aria-label="step.statusLabel">
                            <Icon v-if="step.state === 'checking'" name="spinner" spin/>
                            <Icon v-else-if="step.state === 'pass'" name="check-circle"/>
                            <Icon v-else-if="step.state === 'blocked'" name="help"/>
                            <Icon v-else name="times"/>
                        </span>
                        <button
                            v-if="step.tool === 'htaccess'"
                            type="button"
                            class="bootstrap-error__tool"
                            :title="copy.htaccessTool"
                            :aria-label="copy.htaccessTool"
                            @click="htaccessOpen = true"
                        >
                            <Icon name="wrench"/>
                        </button>
                    </li>
                </ul>

                <div v-if="refreshCountdown !== null" class="bootstrap-error__refresh-notice" role="status">
                    <Icon name="spinner" spin/>
                    <p class="bootstrap-error__refresh-text">{{ refreshNoticeText }}</p>
                    <button
                        type="button"
                        class="btn btn-outline-light pin-btn bootstrap-error__refresh-btn"
                        @click="reloadNow"
                    >
                        {{ copy.refreshNow }}
                    </button>
                </div>

                <div class="bootstrap-error__actions">
                    <button
                        type="button"
                        class="btn btn-light pin-btn bootstrap-error__retry"
                        :disabled="checking || refreshCountdown !== null"
                        @click="runChecks"
                    >
                        <Icon v-if="checking" name="spinner" spin/>
                        <span>{{ checking ? copy.checking : copy.retry }}</span>
                    </button>
                </div>
            </div>

            <p class="bootstrap-error__footnote">{{ copy.footnote }}</p>
        </div>

        <HtaccessModal v-model:open="htaccessOpen" @created="onHtaccessCreated"/>
    </div>
</template>

<script setup>
import {computed, onMounted, onUnmounted, reactive, ref} from 'vue'
import Icon from '@/components/icons/Icon.vue'
import HtaccessModal from '@/components/HtaccessModal.vue'
import logoUrl from '@/assets/images/logo/logo-64.png'
import {
    isBootstrapReady,
    pingInstallerApi,
    resolveSiteEntryUrl,
    runBootstrapCheckPinooxJs,
    runBootstrapChecksSteps12,
    shouldCheckStep3,
} from '@/utils/resolveInstallerApi.js'
import {useInstallStore} from '@/stores/install.js'

const store = useInstallStore()

const htaccessOpen = ref(false)
const checking = ref(false)
const refreshCountdown = ref(null)
const refreshReason = ref(null)
const REFRESH_DELAY_HTACCESS_SECONDS = 5
const REFRESH_DELAY_READY_SECONDS = 3
let refreshTimer = null

const results = reactive({
    rewrite: {state: 'checking'},
    htaccess: {state: 'checking'},
    pinoox_js: {state: 'blocked', blocked_by: 'pending'},
})

const copy = computed(() => {
    const isFa = store.OPTIONS.lang === 'fa'

    if (isFa) {
        return {
            badge: 'خطای بارگذاری',
            title: 'امکان شروع نصب وجود ندارد',
            message: 'مراحل ۱ و ۲ همزمان بررسی می‌شوند. اگر هر دو تأیید شدند ولی هنوز خطا دارید، مرحله ۳ بررسی می‌شود.',
            htaccessTool: 'ایجاد خودکار .htaccess',
            retry: 'بررسی مجدد',
            checking: 'در حال بررسی…',
            footnote: 'پس از رفع مشکل، دوباره بررسی کنید یا صفحه را بارگذاری مجدد نمایید.',
            refreshNoticeHtaccess: (seconds) =>
                `فایل .htaccess ایجاد شد. برای اعمال تغییرات، صفحه تا ${seconds} ثانیه دیگر بارگذاری مجدد می‌شود.`,
            refreshNoticeReady: (seconds) =>
                `همه بررسی‌ها موفق بود. تا ${seconds} ثانیه دیگر برای شروع نصب بارگذاری مجدد می‌شود.`,
            refreshNow: 'بارگذاری الان',
            steps: {
                rewrite: 'URL Rewrite (mod_rewrite در Apache)',
                htaccess: 'فایل .htaccess در ریشه سایت',
                pinoox_js: 'اسکریپت inline bootstrap (__PINOOX__) در قالب',
            },
            status: {
                checking: 'در حال بررسی',
                pass: 'موفق',
                fail: 'ناموفق',
                blocked: 'نیازمند مرحله قبل',
                unknown: 'نامعلوم',
            },
            details: {
                disabled: 'mod_rewrite غیرفعال است',
                missing: 'فایل .htaccess وجود ندارد',
                empty: 'فایل .htaccess خالی است',
                no_pinoox_block: 'بلوک pinoox در .htaccess یافت نشد',
                not_found: '__PINOOX__ در صفحه تعریف نشده (pinoox_bootstrap)',
                invalid: 'محتوای inline bootstrap نامعتبر است',
                twig_missing: 'partials/scripts.twig یا index.twig یافت نشد',
                twig_invalid: 'قالب bootstrap نامعتبر است',
                api_unreachable: 'API از طریق ?_pnx= در دسترس نیست',
                requires_rewrite: 'ابتدا URL Rewrite را فعال کنید',
                requires_htaccess: 'ابتدا .htaccess را اصلاح کنید',
                pending: 'پس از تأیید rewrite و .htaccess بررسی می‌شود',
                ok: 'تأیید شد',
                Apache_mod_rewrite: 'Apache mod_rewrite فعال است',
            },
        }
    }

    return {
        badge: 'Load error',
        title: 'Unable to start installation',
        message: 'Steps 1 and 2 are checked together. If both pass but an error remains, step 3 is checked.',
        htaccessTool: 'Create .htaccess automatically',
        retry: 'Check again',
        checking: 'Checking…',
        footnote: 'After fixing the issue, run the check again or reload this page.',
        refreshNoticeHtaccess: (seconds) =>
            `.htaccess was created. The page will reload in ${seconds} seconds to apply the changes.`,
        refreshNoticeReady: (seconds) =>
            `All checks passed. Reloading in ${seconds} seconds to start installation.`,
        refreshNow: 'Reload now',
        steps: {
            rewrite: 'URL Rewrite (Apache mod_rewrite)',
            htaccess: '.htaccess file in the site root',
            pinoox_js: 'Inline bootstrap script (__PINOOX__) in the theme layout',
        },
        status: {
            checking: 'Checking',
            pass: 'Passed',
            fail: 'Failed',
            blocked: 'Requires previous step',
            unknown: 'Unknown',
        },
        details: {
            disabled: 'mod_rewrite is disabled',
            missing: '.htaccess file is missing',
            empty: '.htaccess file is empty',
            no_pinoox_block: 'Pinoox block not found in .htaccess',
            not_found: '__PINOOX__ is not defined on the page (pinoox_bootstrap)',
            invalid: 'Inline bootstrap content is invalid',
            twig_missing: 'partials/scripts.twig or index.twig was not found',
            twig_invalid: 'Bootstrap template is invalid',
            api_unreachable: 'API is not reachable via ?_pnx=',
            requires_rewrite: 'Enable URL Rewrite first',
            requires_htaccess: 'Fix .htaccess first',
            pending: 'Checked after rewrite and .htaccess are verified',
            ok: 'Verified',
            Apache_mod_rewrite: 'Apache mod_rewrite is enabled',
        },
    }
})

const refreshNoticeText = computed(() => {
    if (refreshCountdown.value === null) {
        return ''
    }

    if (refreshReason.value === 'ready') {
        return copy.value.refreshNoticeReady(refreshCountdown.value)
    }

    return copy.value.refreshNoticeHtaccess(refreshCountdown.value)
})

function detailText(step, data) {
    const details = copy.value.details

    if (data.state === 'blocked') {
        return details[data.blocked_by] ?? details.requires_rewrite
    }

    if (data.state === 'pass') {
        if (step === 'rewrite' && data.detail) {
            const detailKey = String(data.detail).replace(/ /g, '_')

            return details[detailKey] ?? details[data.detail] ?? data.detail
        }

        return details.ok
    }

    if (data.detail && details[data.detail]) {
        return details[data.detail]
    }

    if (data.detail) {
        return data.detail
    }

    if (data.server) {
        return data.server
    }

    return null
}

const steps = computed(() => {
    const order = [
        {key: 'rewrite', number: 1, tool: null},
        {key: 'htaccess', number: 2, tool: 'htaccess'},
        {key: 'pinoox_js', number: 3, tool: null},
    ]

    return order.map(({key, number, tool}) => {
        const data = results[key] ?? {state: 'checking'}
        const idle = key === 'pinoox_js'
            && data.state === 'blocked'
            && data.blocked_by === 'pending'

        return {
            key,
            number,
            tool,
            state: data.state,
            text: copy.value.steps[key],
            detail: detailText(key, data),
            statusLabel: copy.value.status[data.state] ?? data.state,
            focused: number <= 2 || (key === 'pinoox_js' && data.state === 'checking'),
            idle,
        }
    })
})

async function runChecks() {
    clearRefreshTimer()
    refreshCountdown.value = null
    refreshReason.value = null
    checking.value = true

    results.rewrite = {state: 'checking'}
    results.htaccess = {state: 'checking'}
    results.pinoox_js = {state: 'blocked', blocked_by: 'pending'}

    const [ping, steps12] = await Promise.all([
        pingInstallerApi(),
        runBootstrapChecksSteps12(),
    ])

    store.setPreflightPing(ping)

    results.rewrite = steps12.rewrite
    results.htaccess = steps12.htaccess

    if (shouldCheckStep3(steps12, ping)) {
        results.pinoox_js = {state: 'checking'}
        results.pinoox_js = await runBootstrapCheckPinooxJs()
    }

    checking.value = false

    if (isBootstrapReady(ping, results)) {
        scheduleRefresh('ready')
    }
}

function clearRefreshTimer() {
    if (refreshTimer !== null) {
        clearInterval(refreshTimer)
        refreshTimer = null
    }
}

function reloadNow() {
    clearRefreshTimer()

    if (refreshReason.value === 'ready') {
        window.location.href = resolveSiteEntryUrl()
        return
    }

    window.location.reload()
}

function scheduleRefresh(reason) {
    if (refreshCountdown.value !== null) {
        return
    }

    refreshReason.value = reason
    refreshCountdown.value = reason === 'ready'
        ? REFRESH_DELAY_READY_SECONDS
        : REFRESH_DELAY_HTACCESS_SECONDS

    refreshTimer = setInterval(() => {
        if (refreshCountdown.value <= 1) {
            reloadNow()
            return
        }

        refreshCountdown.value -= 1
    }, 1000)
}

function onHtaccessCreated() {
    htaccessOpen.value = false
    clearRefreshTimer()
    refreshReason.value = null
    refreshCountdown.value = null
    scheduleRefresh('htaccess')
}

onMounted(runChecks)
onUnmounted(clearRefreshTimer)
</script>
