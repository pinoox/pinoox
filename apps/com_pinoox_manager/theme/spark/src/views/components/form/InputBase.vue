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
      <Icon :is="icon"/>
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
        :type="type"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :value="modelValue"
        :autocomplete="autocomplete"
        :class="{ 'text-left': direction === 'ltr', 'text-right': direction === 'rtl' }"
        @input="emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Icon from '@/views/components/widgets/Icon.vue';

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
});

const emit = defineEmits(["update:modelValue"]);

const controlClass = computed(() => ({
  'inputWrapper__control--default': props.variant === 'default',
  'inputWrapper__control--glass': props.variant === 'glass',
  'inputWrapper__control--with-icon': Boolean(props.icon),
  'flex-row': props.direction === 'rtl',
  'flex-row-reverse': props.direction === 'ltr',
}));
</script>
