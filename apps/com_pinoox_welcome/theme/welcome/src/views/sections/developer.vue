<template>
    <section class="section section__developer" aria-labelledby="developer-title">
        <div class="container">
            <span class="section__audience section__audience--developer">{{ $t('developerAudience') }}</span>
            <p class="section__developer-eyebrow" aria-hidden="true">{{ $t('developerEyebrow') }}</p>

            <div class="section__developer-grid">
                <div class="section__developer-code" dir="ltr">
                    <div class="section__developer-code-panel">
                        <Highlighter class="section__developer-code--highlighter"></Highlighter>
                    </div>

                    <img
                        v-if="isPersian"
                        class="section__developer-code--image"
                        src="@/assets/media/mock.png"
                        :alt="$t('mockImageAlt')"
                        loading="lazy"
                        width="280"
                        height="560"
                    >
                    <DeveloperPhoneMock v-else class="section__developer-code--image section__developer-code--image-mock" />
                </div>

                <div class="section__developer-content">
                    <h2 id="developer-title" class="section__developer-title gradient-text">{{ $t('becomeDeveloper') }}</h2>
                    <p class="section__developer-text">{{ $t('developerDescription') }}</p>

                    <a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/developer" class="btn btn-primary-outline">
                        <span>{{ $t('register') }}</span>
                        <span class="btn__icon" aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Highlighter from '@/views/components/highlighter.vue';
import DeveloperPhoneMock from '@/views/components/developer-phone-mock.vue';

const { locale } = useI18n();
const isPersian = computed(() => locale.value === 'fa');
</script>
