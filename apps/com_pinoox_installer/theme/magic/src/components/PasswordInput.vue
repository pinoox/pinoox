<template>
    <div class="password-input">
        <input
            :id="id"
            v-model="model"
            :type="inputType"
            :name="name"
            :class="inputClass"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
        >
        <button
            type="button"
            class="password-input__toggle"
            :aria-label="toggleLabel"
            :aria-pressed="visible"
            @click="visible = !visible"
        >
            <Icon :name="visible ? 'eye-off' : 'eye'"/>
        </button>
    </div>
</template>

<script setup>
import {computed, ref} from 'vue'
import {storeToRefs} from 'pinia'
import Icon from '@/components/icons/Icon.vue'
import {useInstallStore} from '@/stores/install.js'

defineProps({
    id: {
        type: String,
        default: '',
    },
    name: {
        type: String,
        default: 'password',
    },
    placeholder: {
        type: String,
        default: 'password',
    },
    autocomplete: {
        type: String,
        default: 'new-password',
    },
    inputClass: {
        type: String,
        default: 'pin-input form-control ltr',
    },
})

const model = defineModel({type: String, default: ''})

const store = useInstallStore()
const {LANG} = storeToRefs(store)

const visible = ref(false)

const inputType = computed(() => (visible.value ? 'text' : 'password'))

const toggleLabel = computed(() => {
    const install = LANG.value?.install ?? {}

    return visible.value
        ? (install.hide_password ?? 'Hide password')
        : (install.show_password ?? 'Show password')
})
</script>
