<template>
  <div
      class="relative flex items-center w-full border border-gray-300 rounded-md overflow-hidden"
      :class="{ 'flex-row': direction === 'rtl', 'flex-row-reverse': direction === 'ltr' }"
  >
   <span
       v-if="prefix"
       :class="{ 'rtl': direction === 'rtl', 'ltr': direction === 'ltr' }"
       class="inputWrapper__prefix bg-gray-900 px-3 py-3 text-gray-300 text-sm"
   >
      {{ prefix }}
    </span>

    <!-- Input Field -->
    <input
        class="inputWrapper__form flex-1 px-2 py-2"
        :type="type"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :value="modelValue"
        :class="{ 'text-left': direction === 'ltr', 'text-right': direction === 'rtl' }"
        @input="emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>

<script setup>
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
});

const emit = defineEmits(["update:modelValue"]);
</script>
