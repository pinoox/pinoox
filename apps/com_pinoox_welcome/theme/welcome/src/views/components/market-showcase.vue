<template>
    <div class="market-ecosystem" role="img" :aria-label="$t('marketImageAlt')">
        <svg
            class="market-ecosystem__canvas"
            viewBox="0 0 400 400"
            aria-hidden="true"
        >
            <defs>
                <linearGradient id="ecosystem-line" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#008AF3" stop-opacity="0.15" />
                    <stop offset="50%" stop-color="#008AF3" stop-opacity="0.55" />
                    <stop offset="100%" stop-color="#F54086" stop-opacity="0.35" />
                </linearGradient>
                <linearGradient id="ecosystem-fold" x1="0%" y1="100%" x2="0%" y2="0%">
                    <stop offset="0%" stop-color="#008AF3" stop-opacity="0" />
                    <stop offset="100%" stop-color="#008AF3" stop-opacity="0.22" />
                </linearGradient>
                <filter id="ecosystem-glow" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur stdDeviation="3" result="blur" />
                    <feMerge>
                        <feMergeNode in="blur" />
                        <feMergeNode in="SourceGraphic" />
                    </feMerge>
                </filter>
            </defs>

            <circle class="market-ecosystem__orbit" cx="200" cy="200" r="150" />

            <polygon
                v-for="(fold, index) in folds"
                :key="`fold-${index}`"
                class="market-ecosystem__fold"
                :points="fold"
                :style="{ animationDelay: `${index * 0.4}s` }"
            />

            <line
                v-for="(line, index) in lines"
                :key="`line-${index}`"
                class="market-ecosystem__link"
                :x1="line.x1"
                :y1="line.y1"
                :x2="line.x2"
                :y2="line.y2"
                :style="{ animationDelay: `${index * 0.25}s` }"
            />

            <circle
                class="market-ecosystem__core-ring"
                cx="200"
                cy="200"
                r="58"
                filter="url(#ecosystem-glow)"
            />
        </svg>

        <div class="market-ecosystem__core">
            <div class="market-ecosystem__core-socket" aria-hidden="true">
                <span
                    v-for="(notch, index) in notches"
                    :key="`notch-${index}`"
                    class="market-ecosystem__core-notch"
                    :style="{ '--notch-angle': `${notch}deg` }"
                ></span>
            </div>
            <div class="market-ecosystem__core-logo">
                <img src="@/assets/media/logo.png" alt="" width="80" height="80">
            </div>
        </div>

        <div
            v-for="node in nodes"
            :key="node.key"
            class="market-ecosystem__node"
            :style="{
                '--node-x': `${node.xPercent}%`,
                '--node-y': `${node.yPercent}%`,
                '--node-angle': `${node.angle}deg`,
                '--node-delay': `${node.delay}s`,
                '--node-color': node.color,
            }"
        >
            <span class="market-ecosystem__node-tab" aria-hidden="true"></span>
            <div class="market-ecosystem__node-ring">
                <div class="market-ecosystem__node-icon">
                    <MarketAppIcon :name="node.key" />
                </div>
            </div>
            <span class="market-ecosystem__node-label">{{ $t(node.labelKey) }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import MarketAppIcon from '@/views/components/icons/market-app-icon.vue';

const VIEW = 400;
const CX = 200;
const CY = 200;
const ORBIT = 150;
const CORE_R = 58;
const NODE_R = 34;
const NODE_COUNT = 5;

const apps = [
    { key: 'shop', labelKey: 'marketAppShop', color: '#FF9C32' },
    { key: 'blog', labelKey: 'marketAppBlog', color: '#8B5CF6' },
    { key: 'panel', labelKey: 'marketAppPanel', color: '#008AF3' },
    { key: 'chat', labelKey: 'marketAppChat', color: '#22C55E' },
    { key: 'forms', labelKey: 'marketAppForms', color: '#F54086' },
];

function polarPoint(angleDeg, radius) {
    const rad = (angleDeg - 90) * (Math.PI / 180);
    return {
        x: CX + radius * Math.cos(rad),
        y: CY + radius * Math.sin(rad),
    };
}

function edgePoint(fromX, fromY, toX, toY, offset) {
    const dx = toX - fromX;
    const dy = toY - fromY;
    const dist = Math.hypot(dx, dy) || 1;
    return {
        x: fromX + (dx / dist) * offset,
        y: fromY + (dy / dist) * offset,
    };
}

function foldTriangle(angleDeg) {
    const tip = polarPoint(angleDeg, CORE_R * 0.35);
    const left = polarPoint(angleDeg - 9, CORE_R + 26);
    const right = polarPoint(angleDeg + 9, CORE_R + 26);
    return `${tip.x},${tip.y} ${left.x},${left.y} ${right.x},${right.y}`;
}

const nodes = computed(() =>
    apps.map((app, index) => {
        const angle = (360 / NODE_COUNT) * index;
        const point = polarPoint(angle, ORBIT);
        return {
            ...app,
            angle,
            x: point.x,
            y: point.y,
            xPercent: (point.x / VIEW) * 100,
            yPercent: (point.y / VIEW) * 100,
            delay: index * 0.35,
        };
    }),
);

const lines = computed(() =>
    nodes.value.map((node) => {
        const start = edgePoint(CX, CY, node.x, node.y, CORE_R + 4);
        const end = edgePoint(node.x, node.y, CX, CY, NODE_R + 6);
        return { x1: start.x, y1: start.y, x2: end.x, y2: end.y };
    }),
);

const folds = computed(() => nodes.value.map((node) => foldTriangle(node.angle)));

const notches = computed(() => nodes.value.map((node) => node.angle));
</script>
