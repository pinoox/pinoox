<template>
    <section class="section section__hero" aria-labelledby="hero-title">
        <div class="section__hero-bg" aria-hidden="true">
            <div class="section__hero-aurora section__hero-aurora--one"></div>
            <div class="section__hero-aurora section__hero-aurora--two"></div>
            <div class="section__hero-glow section__hero-glow--primary"></div>
            <div class="section__hero-glow section__hero-glow--secondary"></div>
            <div class="section__hero-grid"></div>
            <div class="section__hero-curve"></div>
        </div>

        <div class="container section__hero-inner">
            <header class="section__hero-head">
                <h1 id="hero-title" class="section__hero-title title-gradient title-gradient--hero">{{ $t('welcome') }}</h1>
            </header>

            <div class="section__hero-visual">
                <div class="section__hero-visual-ring section__hero-visual-ring--outer" aria-hidden="true"></div>
                <div class="section__hero-visual-ring section__hero-visual-ring--inner" aria-hidden="true"></div>
                <div class="section__hero-visual-orbit" aria-hidden="true"></div>

                <div class="section__hero-stage">
                    <div class="section__hero-float section__hero-float--route" aria-hidden="true">
                        <span class="section__hero-float-label">Route</span>
                        <Transition name="mini-manager-url" mode="out-in">
                            <code :key="floatPath" class="section__hero-float-value">{{ floatPath }}</code>
                        </Transition>
                    </div>
                    <div class="section__hero-float section__hero-float--apps" aria-hidden="true">
                        <span class="section__hero-float-value">12</span>
                        <span class="section__hero-float-label">{{ $t('miniManagerStatApps') }}</span>
                    </div>

                    <div class="section__hero-frame">
                        <div class="section__hero-shine" aria-hidden="true"></div>
                        <MiniManager />
                    </div>

                    <div class="section__hero-shadow" aria-hidden="true"></div>
                </div>
            </div>

            <div class="section__hero-body">
                <div class="section__hero-message">
                    <p class="section__hero-message-lead">{{ $t('managerDescriptionLead') }}</p>
                    <ul class="section__hero-tags" aria-label="Features">
                        <li
                            v-for="tag in highlightTags"
                            :key="tag"
                            class="section__hero-tag"
                        >
                            {{ tag }}
                        </li>
                    </ul>
                </div>

                <div class="section__hero-actions">
                    <a class="btn btn-primary" :href="url.MANAGER">
                        <span>{{ $t('goToManager') }}</span>
                        <span class="btn__icon" aria-hidden="true">→</span>
                    </a>
                    <a
                        class="btn btn-ghost"
                        target="_blank"
                        rel="noopener noreferrer"
                        href="https://pinoox.com/faq"
                    >
                        {{ $t('gettingStarted') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { getUrl } from '@/boot.js';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import MiniManager from '@/views/components/mini-manager.vue';

const url = getUrl();
const { t } = useI18n();

const highlightTags = computed(() => [
    t('heroFeatureSettings'),
    t('heroFeatureContent'),
    t('heroFeatureApps'),
]);

const floatPaths = ['/blog', '/shop', '/chat', '/forms'];
const floatPath = ref(floatPaths[0]);
let floatTimer = null;

onMounted(() => {
    let index = 0;
    floatTimer = setInterval(() => {
        index = (index + 1) % floatPaths.length;
        floatPath.value = floatPaths[index];
    }, 2800);
});

onUnmounted(() => {
    clearInterval(floatTimer);
});
</script>
