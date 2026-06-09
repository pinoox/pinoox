<template>
  <SimpleModal title="اطلاعات صفحه" size="sm" class="modalAppViewInfo">
    <div class="modalAppViewInfo__address" dir="ltr">
      <span class="modalAppViewInfo__addressValue">{{ info.route || '/' }}</span>
    </div>

    <dl class="modalAppViewInfo__meta" dir="rtl">
      <div class="modalAppViewInfo__row">
        <dt>روش درخواست</dt>
        <dd dir="ltr">{{ info.method || 'GET' }}</dd>
      </div>
      <div class="modalAppViewInfo__row">
        <dt>مسیر</dt>
        <dd dir="ltr" class="is-mono">{{ info.path || '/' }}</dd>
      </div>
      <div v-if="info.query" class="modalAppViewInfo__row">
        <dt>Query</dt>
        <dd dir="ltr" class="is-mono">{{ info.query }}</dd>
      </div>
      <div v-if="info.hash" class="modalAppViewInfo__row">
        <dt>Hash</dt>
        <dd dir="ltr" class="is-mono">#{{ info.hash }}</dd>
      </div>
      <div class="modalAppViewInfo__row">
        <dt>پکیج</dt>
        <dd dir="ltr" class="is-mono">{{ info.packageName || '—' }}</dd>
      </div>
      <div class="modalAppViewInfo__row">
        <dt>نام اپ</dt>
        <dd>{{ info.appName || '—' }}</dd>
      </div>
    </dl>

    <template #footer>
      <Button @click="closeModal" label="بستن" variant="dark"/>
    </template>
  </SimpleModal>
</template>

<script setup>
defineOptions({modalGroup: 'default'});

import {closeModal} from '@kolirt/vue-modal';
import SimpleModal from '@/views/components/commons/SimpleModal.vue';
import Button from '@/views/components/widgets/Button.vue';

defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
});
</script>

<style lang="scss">
@use '@/assets/styles/base/variable' as *;

.modalAppViewInfo.vue-modal-content {
  width: min(100%, 26rem);
}

.modalAppViewInfo {
  &__address {
    display: flex;
    align-items: center;
    min-height: 2.15rem;
    padding: 0 0.75rem;
    margin-bottom: 0.9rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 0.5rem;
    background: rgba(0, 0, 0, 0.34);
  }

  &__addressValue {
    width: 100%;
    font-size: 0.8rem;
    color: $color-title;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    text-align: left;
    word-break: break-all;
  }

  &__meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin: 0;
  }

  &__row {
    display: grid;
    grid-template-columns: minmax(4.75rem, 5.5rem) minmax(0, 1fr);
    gap: 0.55rem 0.65rem;
    align-items: start;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);

    &:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    dt {
      margin: 0;
      font-size: 0.76rem;
      color: $color-text;
      font-weight: 500;
      line-height: 1.45;
    }

    dd {
      margin: 0;
      font-size: 0.8rem;
      color: $color-title;
      line-height: 1.45;
      overflow-wrap: anywhere;
      word-break: break-word;

      &.is-mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.76rem;
        text-align: left;
      }
    }
  }
}
</style>
