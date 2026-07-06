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

        <header class="mini-manager__toolbar">

            <span class="mini-manager__brand">Manager</span>

            <div class="mini-manager__breadcrumb" :aria-label="$t('miniManagerUrlHint')">

                <span class="mini-manager__breadcrumb-host">{{ $t('miniManagerUrlHost') }}</span>

                <Transition name="mini-manager-url" mode="out-in">

                    <span :key="currentPath" class="mini-manager__breadcrumb-path">{{ currentPath }}</span>

                </Transition>

            </div>

        </header>



        <div class="mini-manager__workspace">

            <aside class="mini-manager__sidebar" :aria-label="$t('miniManagerDock')">

                <button

                    v-for="view in dockViews"

                    :key="view.key"

                    type="button"

                    class="mini-manager__nav-item"

                    :class="{ 'is-active': activeView === view.key }"

                    :aria-label="$t(view.titleKey)"

                    :aria-pressed="activeView === view.key"

                    @click="selectView(view.key)"

                >

                    <span class="mini-manager__nav-icon" v-html="view.icon"></span>

                </button>

            </aside>



            <main class="mini-manager__main">

                <Transition name="mini-manager-fade" mode="out-in">

                    <div :key="activeView" class="mini-manager__panel">

                        <div v-if="activeView === 'dashboard'" class="mini-manager__dashboard">

                            <div class="mini-manager__hero-card" aria-hidden="true">

                                <div class="mini-manager__hero-glow"></div>

                                <div class="mini-manager__route-flow">

                                    <span class="mini-manager__route-node">{{ $t('miniManagerUrlHost') }}</span>

                                    <span class="mini-manager__route-line"></span>

                                    <Transition name="mini-manager-url" mode="out-in">

                                        <span :key="activeApp.path" class="mini-manager__route-node mini-manager__route-node--path">

                                            {{ activeApp.path }}

                                        </span>

                                    </Transition>

                                </div>

                                <div class="mini-manager__hero-app">

                                    <MarketAppIcon :name="activeApp.icon" />

                                    <span>{{ $t(activeApp.labelKey) }}</span>

                                </div>

                            </div>

                            <div class="mini-manager__stats">

                                <div

                                    v-for="(stat, index) in stats"

                                    :key="stat.key"

                                    class="mini-manager__stat"

                                    :style="{ animationDelay: `${index * 0.12}s` }"

                                >

                                    <span class="mini-manager__stat-value">{{ stat.value }}</span>

                                    <span class="mini-manager__stat-label">{{ $t(stat.labelKey) }}</span>

                                </div>

                            </div>

                        </div>



                        <div v-else-if="activeView === 'apps'" class="mini-manager__launcher">

                            <div

                                v-for="(tile, index) in appTiles"

                                :key="tile.key"

                                class="mini-manager__launcher-tile"

                                :class="{ 'is-active': index === tilePulse }"

                                :style="{ '--tile-color': tile.color, animationDelay: `${index * 0.1}s` }"

                            >

                                <span class="mini-manager__launcher-icon">

                                    <MarketAppIcon :name="tile.icon" />

                                </span>

                                <span class="mini-manager__launcher-name">{{ $t(tile.labelKey) }}</span>

                                <code class="mini-manager__launcher-path">{{ tile.path }}</code>

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

                                <span class="mini-manager__toggle" :class="{ 'is-on': row.on }" aria-hidden="true"></span>

                            </div>

                        </div>

                    </div>

                </Transition>

            </main>

        </div>



        <nav class="mini-manager__dock" aria-hidden="true">

            <div class="mini-manager__dock-shell">

                <button

                    v-for="(tile, index) in appTiles"

                    :key="tile.key"

                    type="button"

                    class="mini-manager__dock-app"

                    :class="{ 'is-active': activeView === 'apps' && index === tilePulse }"

                    :style="{ '--tile-color': tile.color }"

                    tabindex="-1"

                >

                    <MarketAppIcon :name="tile.icon" />

                </button>

            </div>

        </nav>

    </div>

</template>



<script setup>

import { computed, onMounted, onUnmounted, ref } from 'vue';

import MarketAppIcon from '@/views/components/icons/market-app-icon.vue';



const activeView = ref('dashboard');

const tilePulse = ref(0);

let cycleTimer = null;

let tileTimer = null;

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

];



const appTiles = [

    { key: 'shop', path: '/shop', icon: 'shop', color: '#FF9C32', labelKey: 'marketAppShop' },

    { key: 'blog', path: '/blog', icon: 'blog', color: '#8B5CF6', labelKey: 'marketAppBlog' },

    { key: 'chat', path: '/chat', icon: 'chat', color: '#22C55E', labelKey: 'marketAppChat' },

    { key: 'forms', path: '/forms', icon: 'forms', color: '#F54086', labelKey: 'marketAppForms' },

];



const settingRows = [

    { key: 'cache', labelKey: 'miniManagerSettingCache', on: true },

    { key: 'backup', labelKey: 'miniManagerSettingBackup', on: false },

];



const viewKeys = computed(() => dockViews.map((v) => v.key));



const activeApp = computed(() => appTiles[tilePulse.value] || appTiles[0]);



const currentPath = computed(() => {

    if (activeView.value === 'apps' || activeView.value === 'dashboard') {

        return activeApp.value.path;

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



onMounted(() => {

    cycleTimer = setInterval(() => {

        if (!paused) nextView();

    }, 4500);

    tileTimer = setInterval(() => {

        tilePulse.value = (tilePulse.value + 1) % appTiles.length;

    }, 2200);

});



onUnmounted(() => {

    clearInterval(cycleTimer);

    clearInterval(tileTimer);

});

</script>

