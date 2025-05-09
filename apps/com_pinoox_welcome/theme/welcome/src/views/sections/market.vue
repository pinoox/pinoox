<template>
    <section class="section">
        <div class="container">

            <div class="section__market">
                <div class="section__market-content">
                    <h2 class="headline">{{ $t('findApp') }}</h2>
                    <h2 class="gradient-text">{{ $t('downloadApp') }}</h2>
                    <p class="text-dark">{{ $t('marketDescription') }}</p>

                    <a target="_blank" class="btn btn-primary-outline" href="https://pinoox.com/market">{{ $t('goToMarket') }}</a>

                </div>
                <div class="section__market-solar">
                    <Solar></Solar>
                </div>
            </div>

        </div>
    </section>
</template>

<script setup>
import Solar from "@/views/components/solar.vue";
</script>