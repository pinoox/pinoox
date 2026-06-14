<template>
    <section class="section section__hero" aria-labelledby="hero-title">
        <div class="section__hero-bg" aria-hidden="true">
            <div class="section__hero-glow section__hero-glow--primary"></div>
            <div class="section__hero-glow section__hero-glow--accent"></div>
            <div class="section__hero-grid"></div>
            <div class="section__hero-curve"></div>
        </div>

        <div class="container section__hero-inner">
            <div class="section__hero-content">
                <div class="section__hero-copy">
                    <span class="section__hero-badge">
                        <span class="section__hero-badge-dot" aria-hidden="true"></span>
                        {{ $t('heroBadge') }}
                    </span>
                    <h1 id="hero-title" class="section__hero-title gradient-text gradientAnim">{{ $t('welcome') }}</h1>
                    <div class="section__hero-text">
                        <p class="section__hero-text-lead">{{ $t('managerDescriptionLead') }}</p>
                        <p class="section__hero-text-highlight">{{ $t('managerDescriptionHighlight') }}</p>
                    </div>
                </div>

                <div class="section__hero-actions">
                    <a class="btn btn-primary section__hero-btn-primary" :href="url.MANAGER">
                        <span>{{ $t('goToManager') }}</span>
                        <span class="btn__icon" aria-hidden="true">→</span>
                    </a>
                    <a class="btn btn-ghost section__hero-btn-ghost" target="_blank" rel="noopener noreferrer" href="https://pinoox.com/faq">
                        {{ $t('gettingStarted') }}
                    </a>
                </div>
            </div>

            <div class="section__hero-visual">
                <div class="section__hero-visual-orbit" aria-hidden="true"></div>
                <div class="section__hero-frame">
                    <div class="section__hero-shine" aria-hidden="true"></div>
                    <MiniManager />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { getUrl } from '@/boot.js';
import MiniManager from '@/views/components/mini-manager.vue';

const url = getUrl();
</script>
