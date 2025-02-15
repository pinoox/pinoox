<template>
  <div class="inputWrapper flex flex-col gap-2 w-full" :class="{ 'text-red-500': error }">
    <label v-if="label">{{ label }}</label>

    <component
        :is="selectedInput"
        v-bind="props"
        @update:modelValue="emit('update:modelValue', $event)"
    />

    <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
  </div>
</template>

<script setup>
import {computed} from "vue";

// Import input types
import InputText from "./InputText.vue";
import InputNumber from "./InputNumber.vue";

const inputTypes = {
  text: InputText,
  number: InputNumber,
};

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
  options: Array,
});

// Emits
const emit = defineEmits(["update:modelValue"]);

// Compute selected input component
const selectedInput = computed(() => inputTypes[props.type] || InputText);
</script>