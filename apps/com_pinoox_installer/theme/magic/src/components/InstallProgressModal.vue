<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="install-progress-modal"
            role="dialog"
            aria-modal="true"
            :aria-label="title"
        >
            <div class="install-progress-modal__panel">
                <header class="install-progress-modal__header">
                    <div class="install-progress-modal__header-icon">
                        <Icon v-if="isSuccess" name="check-circle"/>
                        <Icon v-else name="spinner" spin/>
                    </div>
                    <div>
                        <h2 class="install-progress-modal__title">{{ title }}</h2>
                        <p class="install-progress-modal__subtitle">{{ subtitle }}</p>
                    </div>
                </header>

                <div class="install-progress-modal__bar" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                    <div class="install-progress-modal__bar-fill" :style="{ width: `${progress}%` }"/>
                </div>

                <p class="install-progress-modal__percent">{{ percentLabel }}</p>

                <ul class="install-progress-modal__steps">
                    <li
                        v-for="(step, index) in steps"
                        :key="step.key"
                        class="install-progress-modal__step"
                        :class="stepStateClass(index)"
                    >
                        <span class="install-progress-modal__step-icon" aria-hidden="true">
                            <Icon v-if="stepIcon(index) === 'spinner'" name="spinner" spin/>
                            <Icon v-else-if="stepIcon(index) === 'check'" name="check"/>
                            <span v-else class="install-progress-modal__step-dot"/>
                        </span>
                        <span class="install-progress-modal__step-text">{{ step.label }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import {computed, onBeforeUnmount, ref, watch} from 'vue'
import {storeToRefs} from 'pinia'
import Icon from '@/components/icons/Icon.vue'
import {useInstallStore} from '@/stores/install.js'

const STEP_KEYS = [
    'setup_progress_save_settings',
    'setup_progress_create_tables',
    'setup_progress_run_patches',
    'setup_progress_app_settings',
    'setup_progress_finish',
]

const STEP_WEIGHTS = [15, 25, 25, 20, 15]
const STEP_INTERVAL_MS = 2800
const SUCCESS_DELAY_MS = 1800

const open = defineModel('open', {type: Boolean, default: false})

const props = defineProps({
    done: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['complete'])

const store = useInstallStore()
const {LANG} = storeToRefs(store)

const activeStep = ref(0)
const progress = ref(0)
const isSuccess = ref(false)
let stepTimer = null

const steps = computed(() => {
    const install = LANG.value?.install ?? {}

    return STEP_KEYS.map((key) => ({
        key,
        label: install[key] ?? key,
    }))
})

const title = computed(() => {
    const install = LANG.value?.install ?? {}

    if (isSuccess.value) {
        return install.setup_progress_success ?? 'Installation complete'
    }

    return install.setup_progress_title ?? 'Installing Pinoox'
})

const subtitle = computed(() => {
    if (isSuccess.value) {
        return ''
    }

    return steps.value[activeStep.value]?.label ?? ''
})

const percentLabel = computed(() => {
    const install = LANG.value?.install ?? {}
    const template = install.setup_progress_percent ?? ':percent% complete'
    const rounded = Math.round(progress.value)

    return template.replace(':percent', String(rounded))
})

watch(open, (value) => {
    if (value) {
        startProgress()
        return
    }

    resetProgress()
})

watch(() => props.done, (value) => {
    if (value && open.value) {
        finishProgress()
    }
})

onBeforeUnmount(() => {
    clearStepTimer()
})

function startProgress() {
    resetProgress()
    progress.value = STEP_WEIGHTS[0] * 0.35
    scheduleNextStep()
}

function resetProgress() {
    clearStepTimer()
    activeStep.value = 0
    progress.value = 0
    isSuccess.value = false
}

function clearStepTimer() {
    if (stepTimer !== null) {
        clearTimeout(stepTimer)
        stepTimer = null
    }
}

function scheduleNextStep() {
    clearStepTimer()

    stepTimer = setTimeout(() => {
        if (!open.value || props.done || isSuccess.value) {
            return
        }

        if (activeStep.value < steps.value.length - 2) {
            activeStep.value += 1
            progress.value = weightedProgress(activeStep.value, 0.4)
            scheduleNextStep()
            return
        }

        progress.value = weightedProgress(steps.value.length - 2, 0.85)
    }, STEP_INTERVAL_MS)
}

function weightedProgress(stepIndex, partial = 1) {
    const completed = STEP_WEIGHTS.slice(0, stepIndex).reduce((sum, weight) => sum + weight, 0)
    const current = STEP_WEIGHTS[stepIndex] ?? 0

    return Math.min(completed + current * partial, 92)
}

function finishProgress() {
    clearStepTimer()
    activeStep.value = steps.value.length - 1
    progress.value = 100
    isSuccess.value = true

    stepTimer = setTimeout(() => {
        emit('complete')
    }, SUCCESS_DELAY_MS)
}

function stepStateClass(index) {
    if (isSuccess.value || index < activeStep.value) {
        return 'install-progress-modal__step--done'
    }

    if (index === activeStep.value) {
        return 'install-progress-modal__step--active'
    }

    return 'install-progress-modal__step--pending'
}

function stepIcon(index) {
    if (isSuccess.value || index < activeStep.value) {
        return 'check'
    }

    if (index === activeStep.value && !isSuccess.value) {
        return 'spinner'
    }

    return 'dot'
}
</script>
