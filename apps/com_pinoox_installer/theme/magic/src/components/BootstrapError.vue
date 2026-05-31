<template>

    <div class="bootstrap-error art-cloud">

        <div class="moving-clouds" aria-hidden="true"/>



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

                        :class="`bootstrap-error__hint--${step.state}`"

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



                <div class="bootstrap-error__actions">

                    <button

                        type="button"

                        class="btn btn-light pin-btn bootstrap-error__retry"

                        :disabled="checking"

                        @click="runChecks"

                    >

                        <Icon v-if="checking" name="spinner" spin/>

                        <span>{{ checking ? copy.checking : copy.retry }}</span>

                    </button>

                </div>

            </div>



            <p class="bootstrap-error__footnote">{{ copy.footnote }}</p>

        </div>



        <HtaccessModal v-model:open="htaccessOpen" @created="runChecks"/>

    </div>

</template>



<script setup>

import {computed, onMounted, reactive, ref} from 'vue'

import Icon from '@/components/icons/Icon.vue'

import HtaccessModal from '@/components/HtaccessModal.vue'

import logoUrl from '@/assets/images/logo/logo-64.png'

import {runBootstrapChecks} from '@/utils/resolveInstallerApi.js'



const htaccessOpen = ref(false)

const checking = ref(false)



const results = reactive({

    rewrite: {state: 'checking'},

    htaccess: {state: 'checking'},

    pinoox_js: {state: 'checking'},

})



const copy = computed(() => {

    const isFa = document.documentElement.lang === 'fa'



    if (isFa) {

        return {

            badge: 'خطای بارگذاری',

            title: 'امکان شروع نصب وجود ندارد',

            message: 'ارتباط نصب‌کننده با سرور به‌درستی برقرار نشد. مراحل زیر به‌صورت خودکار بررسی می‌شوند.',

            htaccessTool: 'ایجاد خودکار .htaccess',

            retry: 'بررسی مجدد',

            checking: 'در حال بررسی…',

            footnote: 'پس از رفع مشکل، دوباره بررسی کنید یا صفحه را بارگذاری مجدد نمایید.',

            steps: {

                rewrite: 'URL Rewrite (mod_rewrite در Apache)',

                htaccess: 'فایل .htaccess در ریشه سایت',

                pinoox_js: 'فایل pinoox.twig و مسیر dist/pinoox.js',

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

                not_found: 'dist/pinoox.js در دسترس نیست (404)',

                invalid: 'محتوای pinoox.js نامعتبر است',

                twig_missing: 'فایل pinoox.twig یافت نشد',

                twig_invalid: 'فایل pinoox.twig نامعتبر است',

                api_unreachable: 'API از طریق ?route= در دسترس نیست',

                requires_rewrite: 'ابتدا URL Rewrite را فعال کنید',

                requires_htaccess: 'ابتدا .htaccess را اصلاح کنید',

                ok: 'تأیید شد',

                Apache_mod_rewrite: 'Apache mod_rewrite فعال است',

            },

        }

    }



    return {

        badge: 'Load error',

        title: 'Unable to start installation',

        message: 'The installer could not connect to the server properly. The steps below are checked automatically.',

        htaccessTool: 'Create .htaccess automatically',

        retry: 'Check again',

        checking: 'Checking…',

        footnote: 'After fixing the issue, run the check again or reload this page.',

        steps: {

            rewrite: 'URL Rewrite (Apache mod_rewrite)',

            htaccess: '.htaccess file in the site root',

            pinoox_js: 'pinoox.twig template and dist/pinoox.js route',

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

            not_found: 'dist/pinoox.js is not reachable (404)',

            invalid: 'pinoox.js content is invalid',

            twig_missing: 'pinoox.twig was not found',

            twig_invalid: 'pinoox.twig is invalid',

            api_unreachable: 'API is not reachable via ?route=',

            requires_rewrite: 'Enable URL Rewrite first',

            requires_htaccess: 'Fix .htaccess first',

            ok: 'Verified',

            Apache_mod_rewrite: 'Apache mod_rewrite is enabled',

        },

    }

})



function detailText(step, data) {

    const details = copy.value.details



    if (data.state === 'blocked') {

        return details[data.blocked_by] ?? details.requires_rewrite

    }



    if (data.state === 'pass') {

        if (step === 'rewrite' && data.detail) {

            return details[data.detail] ?? data.detail

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



        return {

            key,

            number,

            tool,

            state: data.state,

            text: copy.value.steps[key],

            detail: detailText(key, data),

            statusLabel: copy.value.status[data.state] ?? data.state,

        }

    })

})



async function runChecks() {

    checking.value = true



    results.rewrite = {state: 'checking'}

    results.htaccess = {state: 'checking'}

    results.pinoox_js = {state: 'checking'}



    const checked = await runBootstrapChecks()



    results.rewrite = checked.rewrite

    results.htaccess = checked.htaccess

    results.pinoox_js = checked.pinoox_js



    checking.value = false

}



onMounted(runChecks)

</script>


