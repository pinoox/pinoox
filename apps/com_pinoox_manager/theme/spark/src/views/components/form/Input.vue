<template>
  <div class="inputWrapper flex flex-col gap-2 w-full" :class="{ 'text-red-500': error }">
    <label v-if="label">{{ label }}</label>

    <InputBase
        v-bind="props"
        :prefix="prefix"
        @update:modelValue="emit('update:modelValue', $event)"
    />

    <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
  </div>
</template>

<script setup>
import InputBase from "./InputBase.vue";

// Props
const props = defineProps({
  modelValue: [String, Number, Boolean],
  type: {
    type: String,
    default: "text",
  },
  label: String,
  placeholder: String,
  required: Boolean,
  disabled: Boolean,
  error: String,
  direction: {
    type: String,
    default: "rtl",
    validator: (value) => ["ltr", "rtl"].includes(value),
  },
  prefix: String, // New prop for prefix text
});

// Emits
const emit = defineEmits(["update:modelValue"]);
</script>