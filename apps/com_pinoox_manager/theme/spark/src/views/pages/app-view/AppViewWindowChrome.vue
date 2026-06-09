<template>
  <div class="appViewChrome" dir="ltr">
    <button
        type="button"
        class="appViewChrome__btn appViewChrome__btn--minimize"
        title="کوچک‌سازی"
        @click="$emit('minimize')"
    >
      <span class="appViewChrome__glyph appViewChrome__glyph--min"/>
    </button>

    <button
        type="button"
        class="appViewChrome__btn appViewChrome__btn--maximize"
        :title="floating ? 'تمام‌صفحه' : 'پنجره شناور'"
        @click="$emit('toggle-float')"
    >
      <span class="appViewChrome__glyph" :class="floating ? 'appViewChrome__glyph--max' : 'appViewChrome__glyph--restore'"/>
    </button>

    <button
        type="button"
        class="appViewChrome__btn appViewChrome__btn--close"
        title="بستن"
        @click="$emit('close')"
    >
      <span class="appViewChrome__glyph appViewChrome__glyph--close"/>
    </button>
  </div>
</template>

<script setup>
defineProps({
  floating: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['close', 'minimize', 'toggle-float']);
</script>

<style lang="scss" scoped>
.appViewChrome {
  flex-shrink: 0;
  display: inline-flex;
  align-items: stretch;
  height: 2rem;
  border-radius: 0.4rem;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.22);

  &__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    padding: 0;
    border: none;
    border-inline-end: 1px solid rgba(255, 255, 255, 0.06);
    background: transparent;
    color: rgba(255, 255, 255, 0.88);
    cursor: pointer;
    transition: background 0.12s ease, color 0.12s ease;

    &:last-child {
      border-inline-end: none;
    }

    &--minimize:hover,
    &--maximize:hover {
      background: rgba(255, 255, 255, 0.12);
    }

    &--close:hover {
      background: #e81123;
      color: #fff;
    }
  }

  &__glyph {
    display: block;
    position: relative;

    &--min {
      width: 0.55rem;
      height: 1px;
      background: currentColor;
    }

    &--max {
      width: 0.55rem;
      height: 0.55rem;
      border: 1px solid currentColor;
    }

    &--restore {
      width: 0.6rem;
      height: 0.6rem;

      &::before,
      &::after {
        content: '';
        position: absolute;
        border: 1px solid currentColor;
        background: rgba(19, 13, 11, 0.85);
      }

      &::before {
        width: 0.45rem;
        height: 0.45rem;
        top: 0;
        left: 0;
      }

      &::after {
        width: 0.45rem;
        height: 0.45rem;
        bottom: 0;
        right: 0;
      }
    }

    &--close {
      width: 0.65rem;
      height: 0.65rem;

      &::before,
      &::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0.7rem;
        height: 1px;
        background: currentColor;
      }

      &::before {
        transform: translate(-50%, -50%) rotate(45deg);
      }

      &::after {
        transform: translate(-50%, -50%) rotate(-45deg);
      }
    }
  }
}
</style>
