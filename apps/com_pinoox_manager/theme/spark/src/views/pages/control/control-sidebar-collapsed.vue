<template>
  <nav class="sidebarRail" aria-label="کنترل پنل">
    <ul class="sidebarRail__list">
      <li
          v-for="(item, index) in controlMenuItems"
          :key="item.href || item.title"
          class="sidebarRail__item"
      >
        <button
            type="button"
            class="sidebarRail__btn"
            :class="{
              'is-active': isItemActive(item),
              'is-open': openIndex === index,
            }"
            :aria-label="item.title"
            :aria-expanded="item.children ? openIndex === index : undefined"
            @click="onItemClick(item, index, $event)"
        >
          <LucideIcon :name="item.iconName" size="sm"/>
        </button>
      </li>
    </ul>

    <button
        type="button"
        class="sidebarRail__expand"
        aria-label="باز کردن منو"
        @click="expandSidebar"
    >
      <LucideIcon :name="lucideSidebar.chevronLeft" size="sm"/>
    </button>

    <Teleport to="body">
      <transition name="sidebar-flyout-fade">
        <button
            v-if="flyout"
            type="button"
            class="sidebarFlyout__backdrop"
            aria-label="بستن منو"
            @click="closeFlyout"
        />
      </transition>

      <transition name="sidebar-flyout-slide">
        <div
            v-if="flyout"
            class="sidebarFlyout"
            :style="flyoutStyle"
            role="menu"
        >
          <template v-if="flyout.children?.length">
            <div class="sidebarFlyout__head">{{ flyout.title }}</div>
            <button
                v-for="child in flyout.children"
                :key="child.href"
                type="button"
                class="sidebarFlyout__link"
                :class="{ 'is-active': isChildActive(child) }"
                role="menuitem"
                @click="navigate(child.href)"
            >
              {{ child.title }}
            </button>
          </template>

          <button
              v-else
              type="button"
              class="sidebarFlyout__link sidebarFlyout__link--solo"
              role="menuitem"
              @click="navigate(flyout.href)"
          >
            {{ flyout.title }}
          </button>
        </div>
      </transition>
    </Teleport>
  </nav>
</template>

<script setup>
import {computed, nextTick, onBeforeUnmount, ref, watch} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import {lucideSidebar} from '@/const/icons.js';
import LucideIcon from '@/views/components/widgets/LucideIcon.vue';
import {useSidebarStore} from '@/views/composables/useSidebar.js';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';
import {
    controlMenuItems,
    isControlMenuItemActive,
} from '@/views/pages/control/controlMenuItems.js';

const router = useRouter();
const route = useRoute();
const sidebar = useSidebarStore();
const layout = useControlPanelLayoutStore();

const openIndex = ref(null);
const flyout = ref(null);
const flyoutStyle = ref({});
const anchorEl = ref(null);

const routePath = computed(() => route.path);

function isItemActive(item) {
    return isControlMenuItemActive(routePath.value, item);
}

function isChildActive(child) {
    return routePath.value === child.href || routePath.value.startsWith(`${child.href}/`);
}

function expandSidebar() {
    closeFlyout();
    sidebar.setCollapsed(false);
}

function closeFlyout() {
    flyout.value = null;
    flyoutStyle.value = {};
    openIndex.value = null;
    anchorEl.value = null;
}

function clampFlyoutTop(top, height = 160) {
    const margin = 8;
    const maxTop = window.innerHeight - height - margin;

    return Math.max(margin, Math.min(top, maxTop));
}

function updateFlyoutPosition() {
    if (!anchorEl.value) {
        return;
    }

    const rect = anchorEl.value.getBoundingClientRect();
    const gap = 10;
    const width = 220;

    flyoutStyle.value = {
        position: 'fixed',
        top: `${clampFlyoutTop(rect.top)}px`,
        right: `${window.innerWidth - rect.left + gap}px`,
        width: `${width}px`,
        zIndex: 10120,
    };
}

async function openFlyout(item, index, el) {
    if (openIndex.value === index && flyout.value) {
        closeFlyout();
        return;
    }

    openIndex.value = index;
    anchorEl.value = el;
    flyout.value = item.children?.length
        ? {title: item.title, children: item.children}
        : {title: item.title, href: item.href};

    await nextTick();
    updateFlyoutPosition();
}

function onItemClick(item, index, event) {
    if (item.children?.length) {
        openFlyout(item, index, event.currentTarget);
        return;
    }

    navigate(item.href);
}

function navigate(href) {
    if (!href) {
        return;
    }

    closeFlyout();
    layout.closeMobileSidebar();
    router.push(href);
}

function onViewportChange() {
    if (flyout.value) {
        updateFlyoutPosition();
    }
}

watch(() => route.path, () => {
    closeFlyout();
});

watch(() => sidebar.isCollapsed, (collapsed) => {
    if (!collapsed) {
        closeFlyout();
    }
});

window.addEventListener('resize', onViewportChange);
window.addEventListener('scroll', onViewportChange, true);

onBeforeUnmount(() => {
    window.removeEventListener('resize', onViewportChange);
    window.removeEventListener('scroll', onViewportChange, true);
});
</script>
