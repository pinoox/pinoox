<template>
  <div class="marketPage__body">
    <div class="marketSoon">
      <div class="marketSoon__orb marketSoon__orb--one"></div>
      <div class="marketSoon__orb marketSoon__orb--two"></div>
      <div class="marketSoon__orb marketSoon__orb--three"></div>

      <article class="marketSoon__card">
        <div class="marketSoon__hero">
          <div class="marketSoon__icon-ring">
            <Icon :is="saxIcon.market" class="marketSoon__icon"/>
          </div>

          <h2 class="marketSoon__title">دسترسی ویژه به اپلیکیشن‌ها و قالب‌ها</h2>

          <p class="marketSoon__lead">
            مارکت پینوکس در حال آماده‌سازی است تا بتوانید
            <strong>اپلیکیشن‌ها</strong> و <strong>قالب‌ها</strong> را مرور کنید
            و با <strong>یک کلیک</strong> نصب کنید.
          </p>
        </div>

        <div class="marketSoon__features">
          <div v-for="item in features" :key="item.title" class="marketSoon__feature">
            <span class="marketSoon__feature-icon">
              <Icon :is="item.icon" size="md"/>
            </span>
            <strong>{{ item.title }}</strong>
            <p>{{ item.text }}</p>
          </div>
        </div>

        <div class="marketSoon__footer">
          <p>در حال ساخت تجربه‌ای ساده، سریع و حرفه‌ای — منتظر ما باشید.</p>
          <span class="marketSoon__chip">به‌زودی در دسترس</span>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { saxIcon } from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';

const features = [
  {
    icon: saxIcon.apps,
    title: 'اپلیکیشن‌ها',
    text: 'افزونه‌ها و اپ‌های آماده پینوکس',
  },
  {
    icon: saxIcon.appearance,
    title: 'قالب‌ها',
    text: 'انتخاب و اعمال قالب برای هر اپ',
  },
  {
    icon: saxIcon.add,
    title: 'نصب یک‌کلیکی',
    text: 'دانلود و نصب خودکار، بدون دردسر',
  },
];
</script>
