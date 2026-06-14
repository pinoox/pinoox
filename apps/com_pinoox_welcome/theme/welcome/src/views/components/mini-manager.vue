<template>
    <div
        class="mini-manager"
        dir="ltr"
        :aria-label="$t('managerImageAlt')"
        @mouseenter="pauseCycle"
        @mouseleave="resumeCycle"
        @focusin="pauseCycle"
        @focusout="resumeCycle"
    >
        <div class="mini-manager__screen">
            <header class="mini-manager__toolbar">
                <div class="mini-manager__brand">
                    <span>Manager</span>
                </div>
                <span class="mini-manager__status">
                    <span class="mini-manager__status-dot" aria-hidden="true"></span>
                    {{ $t('miniManagerOnline') }}
                </span>
                <time class="mini-manager__clock">{{ clock }}</time>
            </header>

            <div class="mini-manager__url-bar" :aria-label="$t('miniManagerUrlHint')">
                <span class="mini-manager__url-lock" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 11V8a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <span class="mini-manager__url-host">{{ $t('miniManagerUrlHost') }}</span>
                <Transition name="mini-manager-url" mode="out-in">
                    <span :key="currentPath" class="mini-manager__url-path">{{ currentPath }}</span>
                </Transition>
            </div>

            <div class="mini-manager__desktop">
                <Transition name="mini-manager-fade" mode="out-in">
                    <div :key="activeView" class="mini-manager__window">
                        <div class="mini-manager__window-bar">
                            <span class="mini-manager__window-dots" aria-hidden="true">
                                <i></i><i></i><i></i>
                            </span>
                            <span class="mini-manager__window-title">{{ $t(views[activeView].titleKey) }}</span>
                            <span class="mini-manager__window-path">{{ currentPath }}</span>
                        </div>
                        <div class="mini-manager__window-body">
                            <div v-if="activeView === 'dashboard'" class="mini-manager__dashboard">
                                <div
                                    v-for="(stat, index) in stats"
                                    :key="stat.key"
                                    class="mini-manager__stat"
                                    :style="{ animationDelay: `${index * 0.15}s` }"
                                >
                                    <span class="mini-manager__stat-value">{{ stat.value }}</span>
                                    <span class="mini-manager__stat-label">{{ $t(stat.labelKey) }}</span>
                                </div>
                                <div class="mini-manager__chart" aria-hidden="true">
                                    <span
                                        v-for="(h, i) in chartBars"
                                        :key="i"
                                        class="mini-manager__chart-bar"
                                        :style="{ '--bar-h': `${h}%`, animationDelay: `${i * 0.1}s` }"
                                    ></span>
                                </div>
                                <div class="mini-manager__routes" aria-hidden="true">
                                    <div
                                        v-for="(tile, index) in appTiles"
                                        :key="tile.key"
                                        class="mini-manager__route-chip"
                                        :class="{ 'is-active': tilePulse === index }"
                                        :style="{ '--chip-color': tile.color, animationDelay: `${index * 0.1}s` }"
                                    >
                                        <code>{{ tile.path }}</code>
                                        <span>{{ $t(tile.labelKey) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="activeView === 'apps'" class="mini-manager__apps">
                                <div
                                    v-for="(tile, index) in appTiles"
                                    :key="tile.key"
                                    class="mini-manager__app-tile"
                                    :class="{ 'is-active': index === tilePulse }"
                                    :style="{ '--tile-color': tile.color, animationDelay: `${index * 0.12}s` }"
                                >
                                    <MarketAppIcon :name="tile.icon" />
                                    <span class="mini-manager__app-name">{{ $t(tile.labelKey) }}</span>
                                    <code class="mini-manager__app-path">{{ tile.path }}</code>
                                </div>
                            </div>

                            <div v-else class="mini-manager__settings">
                                <div
                                    v-for="(row, index) in settingRows"
                                    :key="row.key"
                                    class="mini-manager__setting-row"
                                    :style="{ animationDelay: `${index * 0.1}s` }"
                                >
                                    <span>{{ $t(row.labelKey) }}</span>
                                    <span
                                        class="mini-manager__toggle"
                                        :class="{ 'is-on': row.on }"
                                        aria-hidden="true"
                                    ></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>

            <nav class="mini-manager__dock" :aria-label="$t('miniManagerDock')">
                <button
                    v-for="view in dockViews"
                    :key="view.key"
                    type="button"
                    class="mini-manager__dock-item"
                    :class="{ 'is-active': activeView === view.key }"
                    :aria-label="$t(view.titleKey)"
                    :aria-pressed="activeView === view.key"
                    @click="selectView(view.key)"
                >
                    <span class="mini-manager__dock-icon" v-html="view.icon"></span>
                    <span class="mini-manager__dock-indicator" aria-hidden="true"></span>
                </button>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import MarketAppIcon from '@/views/components/icons/market-app-icon.vue';

const activeView = ref('dashboard');
const tilePulse = ref(0);
const clock = ref('09:41');
let cycleTimer = null;
let tileTimer = null;
let clockTimer = null;
let paused = false;

const views = {
    dashboard: { titleKey: 'miniManagerDashboard', path: '/manager' },
    apps: { titleKey: 'miniManagerApps', path: '/apps' },
    settings: { titleKey: 'miniManagerSettings', path: '/settings' },
};

const dockViews = [
    {
        key: 'dashboard',
        titleKey: 'miniManagerDashboard',
        icon: '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="8" height="8" rx="2" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="3" width="8" height="5" rx="2" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="10" width="8" height="11" rx="2" stroke="currentColor" stroke-width="1.6"/><rect x="3" y="13" width="8" height="8" rx="2" stroke="currentColor" stroke-width="1.6"/></svg>',
    },
    {
        key: 'apps',
        titleKey: 'miniManagerApps',
        icon: '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="4" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="4" y="14" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="14" width="6" height="6" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg>',
    },
    {
        key: 'settings',
        titleKey: 'miniManagerSettings',
        icon: '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4l1.4-1.4M17 7l1.4-1.4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    },
];

const stats = [
    { key: 'apps', value: '12', labelKey: 'miniManagerStatApps' },
    { key: 'users', value: '248', labelKey: 'miniManagerStatUsers' },
    { key: 'uptime', value: '99%', labelKey: 'miniManagerStatUptime' },
];

const chartBars = [42, 68, 55, 82, 61, 74, 90];

const appTiles = [
    { key: 'shop', path: '/shop', icon: 'shop', color: '#FF9C32', labelKey: 'marketAppShop' },
    { key: 'blog', path: '/blog', icon: 'blog', color: '#8B5CF6', labelKey: 'marketAppBlog' },
    { key: 'chat', path: '/chat', icon: 'chat', color: '#22C55E', labelKey: 'marketAppChat' },
    { key: 'forms', path: '/forms', icon: 'forms', color: '#F54086', labelKey: 'marketAppForms' },
];

const settingRows = [
    { key: 'cache', labelKey: 'miniManagerSettingCache', on: true },
    { key: 'backup', labelKey: 'miniManagerSettingBackup', on: false },
    { key: 'notify', labelKey: 'miniManagerSettingNotify', on: true },
];

const viewKeys = computed(() => dockViews.map((v) => v.key));

const currentPath = computed(() => {
    if (activeView.value === 'apps') {
        return appTiles[tilePulse.value]?.path || views.apps.path;
    }
    return views[activeView.value]?.path || '/manager';
});

function selectView(key) {
    activeView.value = key;
}

function nextView() {
    const index = viewKeys.value.indexOf(activeView.value);
    activeView.value = viewKeys.value[(index + 1) % viewKeys.value.length];
}

function pauseCycle() {
    paused = true;
}

function resumeCycle() {
    paused = false;
}

function updateClock() {
    const now = new Date();
    clock.value = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

onMounted(() => {
    updateClock();
    clockTimer = setInterval(updateClock, 30000);
    cycleTimer = setInterval(() => {
        if (!paused) nextView();
    }, 4200);
    tileTimer = setInterval(() => {
        tilePulse.value = (tilePulse.value + 1) % appTiles.length;
    }, 1800);
});

onUnmounted(() => {
    clearInterval(cycleTimer);
    clearInterval(tileTimer);
    clearInterval(clockTimer);
});
</script>
