<template>
    <section class="section section__market" aria-labelledby="market-title">
        <div class="container">
            <div class="section__market-grid">
                <div class="section__market-content">
                    <span class="section__audience section__audience--light">{{ $t('marketAudience') }}</span>
                    <h2 id="market-title" class="section__market-title gradient-text">{{ $t('downloadApp') }}</h2>
                    <p class="section__market-text">{{ $t('marketDescription') }}</p>

                    <a target="_blank" rel="noopener noreferrer" class="btn btn-primary-outline" href="https://pinoox.com/market">
                        <span>{{ $t('goToMarket') }}</span>
                        <span class="btn__icon" aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="section__market-visual">
                    <MarketShowcase></MarketShowcase>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import MarketShowcase from "@/views/components/market-showcase.vue";
</script>
