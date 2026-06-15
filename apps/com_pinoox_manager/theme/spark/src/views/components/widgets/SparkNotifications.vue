<template>
  <Notifications
      classes="spark-notification-shell"
      position="top left"
      width="100%"
      :max="5"
      :duration="4200"
      :speed="320"
      :pause-on-hover="true"
      :ignore-duplicates="true"
      :close-on-click="false"
      animation-type="css"
      animation-name="sparkNotificationIn"
      class="spark-notifications"
  >
    <template #body="{ item, close }">
      <article
          class="spark-notification"
          :class="notificationClass(item.type)"
          role="alert"
          aria-live="polite"
      >
        <span class="spark-notification__accent" aria-hidden="true"/>

        <span class="spark-notification__icon" aria-hidden="true">
          <Icon :is="iconFor(item.type)"/>
        </span>

        <div class="spark-notification__content">
          <p v-if="item.title" class="spark-notification__title">{{ item.title }}</p>
          <p v-if="item.text" class="spark-notification__text">{{ item.text }}</p>
        </div>

        <button
            type="button"
            class="spark-notification__close"
            aria-label="بستن"
            @click="close"
        >
          <Icon :is="saxIcon.notifyClose"/>
        </button>
      </article>
    </template>
  </Notifications>
</template>

<script setup>
import Notifications from '@kyvg/vue3-notification';
import Icon from '@/views/components/widgets/Icon.vue';
import {saxIcon} from '@/const/icons.js';

const notificationClass = (type) => `spark-notification--${normalizeType(type)}`;

const normalizeType = (type) => {
  if (type === 'warn' || type === 'warning')
    return 'warn';

  if (type === 'error' || type === 'danger')
    return 'error';

  if (type === 'success')
    return 'success';

  return 'info';
};

const iconFor = (type) => {
  const map = {
    success: saxIcon.notifySuccess,
    error: saxIcon.notifyError,
    warn: saxIcon.notifyWarn,
    info: saxIcon.notifyInfo,
  };

  return map[normalizeType(type)] || saxIcon.notifyInfo;
};
</script>
