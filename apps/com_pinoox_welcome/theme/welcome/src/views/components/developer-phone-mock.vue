<template>
    <div class="developer-mock-phone" dir="ltr" :aria-label="$t('mockImageAlt')">
        <div class="developer-mock-phone__device">
            <div class="developer-mock-phone__notch" aria-hidden="true"></div>

            <header class="developer-mock-phone__header">
                <span class="developer-mock-phone__avatar" aria-hidden="true"></span>
                <button type="button" class="developer-mock-phone__menu" aria-hidden="true">
                    <span></span><span></span><span></span>
                </button>
            </header>

            <div class="developer-mock-phone__title-wrap">
                <h3 class="developer-mock-phone__title">{{ $t('mockPhoneTitle') }}</h3>
                <p class="developer-mock-phone__subtitle">{{ $t('mockPhoneSubtitle') }}</p>
            </div>

            <ul class="developer-mock-phone__list">
                <li
                    v-for="(item, index) in items"
                    :key="item.key"
                    class="developer-mock-phone__card"
                    :class="{ 'is-active': activeCard === index }"
                    :style="{ animationDelay: `${index * 0.12}s` }"
                >
                    <div class="developer-mock-phone__card-main">
                        <span class="developer-mock-phone__thumb" :style="{ '--thumb-color': item.color }" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path :d="item.icon" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div class="developer-mock-phone__card-body">
                            <strong>{{ $t(item.titleKey) }}</strong>
                            <span class="developer-mock-phone__price">{{ $t(item.priceKey) }}</span>
                            <span class="developer-mock-phone__meta">{{ $t(item.metaKey) }}</span>
                        </div>
                    </div>
                    <div class="developer-mock-phone__actions" aria-hidden="true">
                        <span class="developer-mock-phone__action developer-mock-phone__action--edit"></span>
                        <span class="developer-mock-phone__action developer-mock-phone__action--delete"></span>
                    </div>
                </li>
            </ul>

            <div class="developer-mock-phone__total">
                <span>{{ $t('mockPhoneTotal') }}</span>
                <strong>{{ $t('mockPhoneTotalValue') }}</strong>
            </div>

            <nav class="developer-mock-phone__nav" aria-hidden="true">
                <span></span><span></span><span class="is-cart"></span><span></span>
                <button type="button" class="developer-mock-phone__fab">+</button>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const activeCard = ref(1);
let cardTimer = null;

const items = [
    {
        key: 'sneakers',
        titleKey: 'mockPhoneItemSneakers',
        priceKey: 'mockPhonePriceSneakers',
        metaKey: 'mockPhoneMetaSneakers',
        color: '#f8d7df',
        icon: 'M4 18h16M6 14l3-8h6l3 8M8 14h8',
    },
    {
        key: 'runner',
        titleKey: 'mockPhoneItemRunner',
        priceKey: 'mockPhonePriceRunner',
        metaKey: 'mockPhoneMetaRunner',
        color: '#dfe8f8',
        icon: 'M5 17h14M7 13l2-6h6l2 6M9 13h6',
    },
    {
        key: 'classic',
        titleKey: 'mockPhoneItemClassic',
        priceKey: 'mockPhonePriceClassic',
        metaKey: 'mockPhoneMetaClassic',
        color: '#f7e6d4',
        icon: 'M4 16h16M6 12l2-5h8l2 5M8 12h8',
    },
];

onMounted(() => {
    cardTimer = setInterval(() => {
        activeCard.value = (activeCard.value + 1) % items.length;
    }, 2600);
});

onUnmounted(() => {
    clearInterval(cardTimer);
});
</script>
