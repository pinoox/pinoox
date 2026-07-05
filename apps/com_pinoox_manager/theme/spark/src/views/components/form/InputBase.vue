<template>
  <div
      class="inputWrapper__control"
      :class="controlClass"
  >
    <span
        v-if="icon"
        class="inputWrapper__icon"
        aria-hidden="true"
    >
      <Icon :is="icon" size="sm"/>
    </span>

    <span
        v-if="prefix"
        :class="{ 'rtl': direction === 'rtl', 'ltr': direction === 'ltr' }"
        class="inputWrapper__prefix"
    >
      {{ prefix }}
    </span>

    <input
        class="inputWrapper__form"
        :type="inputType"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :value="modelValue"
        :autocomplete="autocomplete"
        :class="{ 'text-left': direction === 'ltr', 'text-right': direction === 'rtl' }"
        @input="emit('update:modelValue', $event.target.value)"
    />

    <button
        v-if="showPasswordToggle && type === 'password'"
        type="button"
        class="inputWrapper__toggle"
        :aria-label="passwordVisible ? 'مخفی‌کردن رمز عبور' : 'نمایش رمز عبور'"
        :aria-pressed="passwordVisible"
        tabindex="-1"
        @click="passwordVisible = !passwordVisible"
    >
      <Icon :is="passwordVisible ? saxIcon.eyeOff : saxIcon.eye" size="sm"/>
    </button>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import Icon from '@/views/components/widgets/Icon.vue';
import { saxIcon } from '@/const/icons.js';

const props = defineProps({
  modelValue: [String, Number],
  type: {
    type: String,
    default: "text",
    validator: (value) => ["text", "number", "password"].includes(value),
  },
  placeholder: String,
  required: Boolean,
  disabled: Boolean,
  direction: {
    type: String,
    default: "rtl",
    validator: (value) => ["ltr", "rtl"].includes(value),
  },
  prefix: String,
  autocomplete: {
    type: String,
    default: undefined,
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'glass'].includes(value),
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
  showPasswordToggle: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue"]);

const passwordVisible = ref(false);

watch(() => props.type, () => {
  passwordVisible.value = false;
});

const inputType = computed(() => {
  if (props.type === 'password' && props.showPasswordToggle && passwordVisible.value) {
    return 'text';
  }

  return props.type;
});

const controlClass = computed(() => ({
  'inputWrapper__control--default': props.variant === 'default',
  'inputWrapper__control--glass': props.variant === 'glass',
  'inputWrapper__control--with-icon': Boolean(props.icon),
  'inputWrapper__control--with-toggle': props.showPasswordToggle && props.type === 'password',
  'flex-row': props.direction === 'rtl',
  'flex-row-reverse': props.direction === 'ltr',
}));
</script>
