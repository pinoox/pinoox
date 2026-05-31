<template>
    <div class="install-alert install-alert--danger" role="alert">
        <div class="install-alert__icon">
            <Icon name="times"/>
        </div>
        <div class="install-alert__body">
            <h3 class="install-alert__title">{{ error.title }}</h3>
            <p class="install-alert__message">{{ error.message }}</p>
            <ul v-if="error.hints?.length" class="install-alert__hints">
                <li v-for="(hint, index) in error.hints" :key="index">
                    <span>{{ hintText(hint) }}</span>
                    <button
                        v-if="hintTool(hint) === 'htaccess'"
                        type="button"
                        class="install-alert__tool"
                        :title="toolLabel"
                        :aria-label="toolLabel"
                        @click="htaccessOpen = true"
                    >
                        <Icon name="wrench"/>
                    </button>
                </li>
            </ul>
            <p v-if="error.apiUrl" class="install-alert__meta">
                <code>{{ error.apiUrl }}</code>
            </p>
            <button
                v-if="showRetry"
                type="button"
                class="btn btn-light pin-btn install-alert__retry"
                @click="$emit('retry')"
            >
                {{ retryLabel }}
            </button>
        </div>

        <HtaccessModal v-model:open="htaccessOpen"/>
    </div>
</template>

<script setup>
import {computed, ref} from 'vue'
import Icon from '@/components/icons/Icon.vue'
import HtaccessModal from '@/components/HtaccessModal.vue'

defineProps({
    error: {
        type: Object,
        required: true,
    },
    retryLabel: {
        type: String,
        default: 'Retry',
    },
    showRetry: {
        type: Boolean,
        default: true,
    },
})

defineEmits(['retry'])

const htaccessOpen = ref(false)

const toolLabel = computed(() =>
    document.documentElement.lang === 'fa'
        ? 'ایجاد خودکار .htaccess'
        : 'Create .htaccess automatically'
)

function hintText(hint) {
    return typeof hint === 'string' ? hint : hint.text
}

function hintTool(hint) {
    return typeof hint === 'object' ? hint.tool : null
}
</script>
