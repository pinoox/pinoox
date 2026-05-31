<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="htaccess-modal"
            role="dialog"
            aria-modal="true"
            :aria-label="copy.title"
            @click.self="close"
        >
            <div class="htaccess-modal__panel">
                <header class="htaccess-modal__header">
                    <div class="htaccess-modal__header-icon">
                        <Icon name="wrench"/>
                    </div>
                    <div>
                        <h2 class="htaccess-modal__title">{{ copy.title }}</h2>
                        <p class="htaccess-modal__subtitle">{{ copy.subtitle }}</p>
                    </div>
                    <button
                        type="button"
                        class="htaccess-modal__close"
                        :aria-label="copy.close"
                        @click="close"
                    >
                        <Icon name="times"/>
                    </button>
                </header>

                <div v-if="loading" class="htaccess-modal__loading">
                    <Icon name="spinner" spin/>
                    <span>{{ copy.loading }}</span>
                </div>

                <template v-else>
                    <p
                        v-if="feedback"
                        class="htaccess-modal__feedback"
                        :class="`htaccess-modal__feedback--${feedback.type}`"
                    >
                        {{ feedback.text }}
                    </p>

                    <pre class="htaccess-modal__preview"><code>{{ preview }}</code></pre>

                    <div class="htaccess-modal__actions">
                        <button
                            type="button"
                            class="btn btn-outline-light pin-btn"
                            @click="close"
                        >
                            {{ copy.close }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-light pin-btn"
                            :disabled="!canCreate || creating"
                            @click="createFile"
                        >
                            <Icon v-if="creating" name="spinner" spin/>
                            <span>{{ copy.create }}</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import {computed, ref, watch} from 'vue'
import Icon from '@/components/icons/Icon.vue'
import {createHtaccessFile, fetchHtaccessStatus} from '@/utils/resolveInstallerApi.js'

const open = defineModel('open', {type: Boolean, default: false})
const emit = defineEmits(['created'])

const loading = ref(false)
const creating = ref(false)
const preview = ref('')
const canCreate = ref(false)
const feedback = ref(null)

const copy = computed(() => {
    const isFa = document.documentElement.lang === 'fa'

    if (isFa) {
        return {
            title: 'ایجاد خودکار .htaccess',
            subtitle: 'اگر فایل .htaccess وجود ندارد یا خالی است، می‌توانید آن را با یک کلیک بسازید.',
            loading: 'در حال بررسی…',
            create: 'ایجاد .htaccess',
            close: 'بستن',
            exists: 'فایل .htaccess از قبل وجود دارد.',
            created: 'فایل .htaccess با موفقیت ایجاد شد. صفحه را دوباره بارگذاری کنید.',
            occupied: 'فایل .htaccess از قبل محتوا دارد و بازنویسی نشد.',
            not_writable: 'پوشه ریشه سایت قابل نوشتن نیست. دسترسی‌ها را بررسی کنید.',
            write_failed: 'ایجاد فایل .htaccess ناموفق بود.',
            load_failed: 'بررسی وضعیت .htaccess ممکن نشد.',
        }
    }

    return {
        title: 'Create .htaccess automatically',
        subtitle: 'If .htaccess is missing or empty, you can generate it with one click.',
        loading: 'Checking…',
        create: 'Create .htaccess',
        close: 'Close',
        exists: '.htaccess already exists.',
        created: '.htaccess was created successfully. Reload the page.',
        occupied: '.htaccess already contains other rules and was not overwritten.',
        not_writable: 'The site root is not writable. Check folder permissions.',
        write_failed: 'Could not create .htaccess.',
        load_failed: 'Could not check .htaccess status.',
    }
})

watch(open, (value) => {
    if (value) {
        loadStatus()
    } else {
        resetState()
    }
})

function resetState() {
    loading.value = false
    creating.value = false
    preview.value = ''
    canCreate.value = false
    feedback.value = null
}

function close() {
    open.value = false
}

function stateMessage(state) {
    const map = {
        exists: copy.value.exists,
        created: copy.value.created,
        occupied: copy.value.occupied,
        not_writable: copy.value.not_writable,
        write_failed: copy.value.write_failed,
    }

    return map[state] ?? copy.value.load_failed
}

async function loadStatus() {
    loading.value = true
    feedback.value = null

    try {
        const data = await fetchHtaccessStatus()

        preview.value = data.content ?? ''
        canCreate.value = Boolean(data.can_create)

        if (data.has_pinoox) {
            feedback.value = {type: 'info', text: copy.value.exists}
            canCreate.value = false
        } else if (!data.can_create && data.exists) {
            feedback.value = {type: 'warn', text: copy.value.occupied}
        } else if (!data.writable) {
            feedback.value = {type: 'warn', text: copy.value.not_writable}
        }
    } catch {
        feedback.value = {type: 'error', text: copy.value.load_failed}
        canCreate.value = false
    } finally {
        loading.value = false
    }
}

async function createFile() {
    creating.value = true

    try {
        const data = await createHtaccessFile()
        const message = stateMessage(data.state)
        const type = data.ok ? 'success' : 'warn'

        feedback.value = {type, text: message}
        canCreate.value = false

        if (data.created) {
            emit('created')
            await loadStatus()
        }
    } catch {
        feedback.value = {type: 'error', text: copy.value.write_failed}
    } finally {
        creating.value = false
    }
}
</script>
