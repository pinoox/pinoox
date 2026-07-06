<template>
  <div ref="root" class="darkSelect" :class="{ 'is-open': open, 'is-rtl': direction === 'rtl' }">
    <label v-if="label" class="darkSelect__label">{{ label }}</label>
    <button
        type="button"
        class="darkSelect__trigger"
        :aria-expanded="open"
        @click="open = !open"
    >
      <span class="darkSelect__value">{{ selectedLabel }}</span>
      <span class="darkSelect__chevron" aria-hidden="true"/>
    </button>
    <ul v-if="open" class="darkSelect__menu" role="listbox">
      <li
          v-for="option in options"
          :key="option.value"
          role="option"
          class="darkSelect__option"
          :class="{ 'is-active': modelValue === option.value }"
          :aria-selected="modelValue === option.value"
          @click="pick(option.value)"
      >
        {{ option.label }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import {computed, ref} from 'vue';
import {onClickOutside} from '@vueuse/core';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  label: String,
  options: {
    type: Array,
    required: true,
  },
  direction: {
    type: String,
    default: 'ltr',
    validator: (value) => ['ltr', 'rtl'].includes(value),
  },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const root = ref(null);

onClickOutside(root, () => {
  open.value = false;
});

const selectedLabel = computed(() => {
  const match = props.options.find((option) => option.value === props.modelValue);

  return match?.label ?? String(props.modelValue || '—');
});

function pick(value) {
  emit('update:modelValue', value);
  open.value = false;
}
</script>

<style lang="scss" scoped>
.darkSelect {
  position: relative;
  display: grid;
  gap: 0.35rem;

  &.is-rtl {
    .darkSelect__trigger,
    .darkSelect__menu {
      direction: rtl;
      text-align: right;
    }
  }

  &__label {
    font-size: 0.82rem;
    opacity: 0.8;
  }

  &__trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.55rem 0.65rem;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 0.55rem;
    background: rgba(12, 16, 26, 0.92);
    color: #f3f4f6;
    font-size: 0.85rem;
    line-height: 1.4;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;

    &:hover {
      border-color: rgba(255, 255, 255, 0.22);
      background: rgba(18, 22, 34, 0.96);
    }

    .is-open & {
      border-color: rgba(var(--color-primary-rgb, 99, 102, 241), 0.45);
      background: rgba(18, 22, 34, 0.98);
    }
  }

  &__value {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__chevron {
    width: 0.45rem;
    height: 0.45rem;
    border-right: 2px solid rgba(255, 255, 255, 0.65);
    border-bottom: 2px solid rgba(255, 255, 255, 0.65);
    transform: rotate(45deg) translateY(-1px);
    flex-shrink: 0;
    transition: transform 0.15s ease;

    .is-open & {
      transform: rotate(-135deg) translateY(1px);
    }
  }

  &__menu {
    position: absolute;
    z-index: 5;
    top: calc(100% + 0.3rem);
    left: 0;
    right: 0;
    margin: 0;
    padding: 0.3rem;
    list-style: none;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 0.6rem;
    background: rgba(14, 18, 30, 0.98);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.42);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    max-height: 11rem;
    overflow-y: auto;
  }

  &__option {
    padding: 0.5rem 0.6rem;
    border-radius: 0.45rem;
    font-size: 0.84rem;
    color: rgba(255, 255, 255, 0.88);
    cursor: pointer;
    transition: background 0.12s ease, color 0.12s ease;

    &:hover {
      background: rgba(255, 255, 255, 0.08);
      color: #fff;
    }

    &.is-active {
      background: rgba(var(--color-primary-rgb, 99, 102, 241), 0.22);
      color: #e0e7ff;
      font-weight: 600;
    }
  }
}
</style>
