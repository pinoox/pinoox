<template>
  <input
      class="inputWrapper__form"
      type="number"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :value="modelValue"
      @input="updateValue"
  />

</template>

<script setup>

const props = defineProps({
  modelValue: String,
  placeholder: String,
  required: Boolean,
  disabled: Boolean,
});

const emit = defineEmits(["update:modelValue"]);

const updateValue = (event) => emit("update:modelValue", event.target.value);
</script>