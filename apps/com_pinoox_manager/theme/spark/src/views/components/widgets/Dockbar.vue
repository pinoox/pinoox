<template>
  <div
      class="dockbar"
      :class="toneClass"
      v-show="isShow"
      ref="dockRoot"
  >
    <Teleport to="body">
      <transition name="dock-fade">
        <div
            v-if="editOpen"
            class="dockbar__backdrop"
            @click="closeEdit"
        />
      </transition>

      <transition name="dock-tooltip">
        <div
            v-if="tooltip && !editOpen"
            ref="tooltipEl"
            class="dockbar__tooltip"
            :style="tooltipStyle"
            role="tooltip"
        >
          <span class="dockbar__tooltip-label">{{ tooltip.name }}</span>
          <span class="dockbar__tooltip-arrow" aria-hidden="true"/>
        </div>
      </transition>
    </Teleport>

    <transition name="slide-up" appear>
      <div class="dockbar__inner">
        <transition name="dock-slide">
          <button
              v-if="editOpen"
              type="button"
              class="dockbar__done"
              @click="closeEdit"
          >
            تمام
          </button>
        </transition>

        <transition name="dock-slide">
          <div
              v-if="editOpen && unpinnedApps.length"
              class="dockbar__tray"
          >
            <p class="dockbar__tray-hint">برای افزودن به داک ضربه بزنید</p>
            <div class="dockbar__tray-scroll">
              <button
                  v-for="app in unpinnedApps"
                  :key="app.package_name"
                  type="button"
                  class="dockbar__tray-item"
                  @click="pinApp(app.package_name)"
              >
                <img :src="app.icon" :alt="app.name" class="dockbar__tray-icon">
                <span class="dockbar__tray-name">{{ app.name }}</span>
              </button>
            </div>
          </div>
        </transition>

        <div
            class="dockbar__shell"
            :class="{ 'is-editing': editOpen }"
            @pointerdown="onShellPressStart"
            @mouseleave="onDockLeave"
        >
          <dock-wrapper
              v-show="isShow"
              :size="size"
              :padding="padding"
              :gap="gap"
              :max-scale="maxScale"
              :max-range="maxRange"
              :disabled="disabled || editOpen"
              :direction="direction"
              :position="position"
          >
            <dock-item
                v-for="item in systemApps"
                :key="item.id"
            >
              <div
                  class="item"
                  :class="{
                    'item--jiggle': editOpen,
                    'item--app': !!item.image,
                    'item--glyph': !item.image,
                  }"
                  :style="jiggleStyle(item.id)"
                  @click="onItemClick(item)"
                  @mouseenter="onItemHover(item, $event)"
                  @mousemove="onItemMove(item, $event)"
              >
                <img v-if="item.image" :src="item.image" :alt="item.name" class="item-image"/>
                <Icon v-else class="item-icon" :is="item.icon"/>
              </div>
            </dock-item>

            <dock-separator v-if="apps.length > 0 || (editOpen && unpinnedApps.length)"></dock-separator>

            <dock-item
                v-for="item in dockAppsWithMinimized"
                :key="item.id"
            >
              <div
                  class="item"
                  :class="{
                    'item--jiggle': editOpen,
                    'item--app': !!item.image,
                    'item--glyph': !item.image,
                    'item--open': isAppOpen(item.id),
                    'item--active': isAppActive(item.id),
                  }"
                  :style="jiggleStyle(item.id)"
                  @click="onItemClick(item)"
                  @mouseenter="onItemHover(item, $event)"
                  @mousemove="onItemMove(item, $event)"
              >
                <img v-if="item.image" :src="item.image" :alt="item.name" class="item-image"/>
                <Icon v-else-if="item.icon" class="item-icon" :is="item.icon"/>
                <button
                    v-if="editOpen"
                    type="button"
                    class="item__badge item__badge--remove"
                    aria-label="حذف از داک"
                    @click.stop="unpinApp(item.id)"
                >
                  <span aria-hidden="true">−</span>
                </button>
              </div>
            </dock-item>

            <dock-item v-if="editOpen && unpinnedApps.length">
              <div class="item item--add item--jiggle" :style="jiggleStyle('add')">
                <span class="item__plus">+</span>
              </div>
            </dock-item>
          </dock-wrapper>
        </div>

        <p v-if="!editOpen" class="dockbar__hint">
          <span class="dockbar__hint-pill">برای ویرایش، داک را نگه دارید</span>
        </p>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useBackground } from '@/views/composables/useBackground.js';
import { useDockBackdropTone } from '@/views/composables/useDockBackdropTone.js';
import { systemDockApps, useDockApps } from '@/views/composables/useDockApps.js';
import { useAppViewWindowStore } from '@/stores/modules/appViewWindow.js';

const props = defineProps({
  size: { type: Number, default: 52 },
  padding: { type: Number, default: 12 },
  gap: { type: Number, default: 12 },
  maxScale: { type: Number, default: 1.5 },
  maxRange: { type: Number, default: 200 },
  disabled: { type: Boolean, default: false },
  direction: { type: String, default: 'horizontal' },
  position: { type: String, default: 'bottom' },
  apps: {
    type: Array,
    default: () => [],
  },
  systemApps: {
    type: Array,
    default: () => systemDockApps,
  },
});

const LONG_PRESS_MS = 520;
const MOVE_TOLERANCE = 10;
const TOOLTIP_SHOW_DELAY = 200;
const TOOLTIP_HIDE_DELAY = 100;
const TOOLTIP_GAP = 14;

const router = useRouter();
const appViewWindow = useAppViewWindowStore();
const { selectedBackground } = useBackground();
const { unpinnedApps, toggleDockPin } = useDockApps();

const dockAppsWithMinimized = computed(() => {
  const list = [...props.apps];
  const minimized = appViewWindow.minimized;

  if (!minimized?.package_name) {
    return list;
  }

  if (list.some((item) => item.id === minimized.package_name)) {
    return list;
  }

  list.push({
    id: minimized.package_name,
    name: minimized.appName ?? minimized.package_name,
    image: minimized.icon,
    route: {name: 'app-view', params: {package_name: minimized.package_name}},
    isMinimizedOnly: true,
  });

  return list;
});

function isMinimizedOpen(packageName) {
  return appViewWindow.minimized?.package_name === packageName;
}

function isAppOpen(packageName) {
  return appViewWindow.isPackageOpen(packageName);
}

function isAppActive(packageName) {
  return appViewWindow.activePackage === packageName;
}

const isShow = ref(false);
const size = ref(props.size);
const editOpen = ref(false);
const tooltip = ref(null);
const tooltipEl = ref(null);
const dockRoot = ref(null);

const { tone, remeasure } = useDockBackdropTone(selectedBackground, dockRoot);

const toneClass = computed(() => `dockbar--tone-${tone.value}`);

let hideTooltipTimer = null;
let showTooltipTimer = null;
let pressTimer = null;
let pressCleanup = null;

const tooltipStyle = computed(() => {
  if (!tooltip.value)
    return {};

  return {
    left: `${tooltip.value.x}px`,
    top: `${tooltip.value.y}px`,
    '--tooltip-shift-x': `${tooltip.value.shiftX ?? 0}px`,
  };
});

function measureTooltip(el) {
  const rect = el.getBoundingClientRect();
  const anchorX = rect.left + rect.width / 2;
  const margin = 10;
  let shiftX = 0;
  let x = anchorX;

  if (tooltipEl.value) {
    const tipRect = tooltipEl.value.getBoundingClientRect();
    const half = tipRect.width / 2;

    if (anchorX - half < margin)
      shiftX = margin - (anchorX - half);

    if (anchorX + half > window.innerWidth - margin)
      shiftX = (window.innerWidth - margin) - (anchorX + half);
  }

  return {
    x,
    y: rect.top - TOOLTIP_GAP,
    shiftX,
  };
}

function setTooltip(item, el) {
  const pos = measureTooltip(el);

  tooltip.value = {
    id: item.id,
    name: item.name,
    ...pos,
  };

  nextTick(() => {
    if (!tooltip.value || tooltip.value.id !== item.id)
      return;

    const adjusted = measureTooltip(el);
    tooltip.value = {
      ...tooltip.value,
      ...adjusted,
    };
  });
}

function hideTooltip() {
  hideTooltipTimer = setTimeout(() => {
    tooltip.value = null;
  }, TOOLTIP_HIDE_DELAY);
}

function clearTooltipTimers() {
  clearTimeout(hideTooltipTimer);
  clearTimeout(showTooltipTimer);
  hideTooltipTimer = null;
  showTooltipTimer = null;
}

function jiggleStyle(id) {
  const seed = String(id).split('').reduce((sum, char) => sum + char.charCodeAt(0), 0);
  const delay = (seed % 7) * 0.04;

  return { animationDelay: `${delay}s` };
}

function open(item) {
  router.push(item.route);
}

function onItemClick(item) {
  if (editOpen.value)
    return;

  if (isMinimizedOpen(item.id)) {
    appViewWindow.restore();
    appViewWindow.openFullscreen(item.id);
    router.push({name: 'app-view', params: {package_name: item.id}});
    return;
  }

  open(item);
}

function onItemHover(item, event) {
  if (editOpen.value)
    return;

  clearTooltipTimers();

  const el = event.currentTarget;
  const isSwitch = tooltip.value && tooltip.value.id !== item.id;

  if (tooltip.value?.id === item.id) {
    setTooltip(item, el);
    return;
  }

  if (isSwitch) {
    setTooltip(item, el);
    return;
  }

  showTooltipTimer = setTimeout(() => {
    setTooltip(item, el);
  }, TOOLTIP_SHOW_DELAY);
}

function onItemMove(item, event) {
  if (editOpen.value || tooltip.value?.id !== item.id)
    return;

  const pos = measureTooltip(event.currentTarget);
  tooltip.value = {
    ...tooltip.value,
    ...pos,
  };
}

function onDockLeave(event) {
  if (editOpen.value)
    return;

  const related = event.relatedTarget;
  if (related && event.currentTarget.contains(related))
    return;

  clearTooltipTimers();
  hideTooltip();
}

function enterEdit() {
  editOpen.value = true;
  clearTooltipTimers();
  tooltip.value = null;
}

function closeEdit() {
  editOpen.value = false;
  clearTooltipTimers();
  tooltip.value = null;
}

function clearPressTimer() {
  if (pressTimer) {
    clearTimeout(pressTimer);
    pressTimer = null;
  }

  if (pressCleanup) {
    pressCleanup();
    pressCleanup = null;
  }
}

function onShellPressStart(event) {
  if (editOpen.value || event.button > 0)
    return;

  const target = event.target;
  if (target.closest('.item__badge'))
    return;

  const startX = event.clientX;
  const startY = event.clientY;

  clearPressTimer();

  pressTimer = setTimeout(() => {
    pressTimer = null;
    enterEdit();
    if (pressCleanup)
      pressCleanup();
  }, LONG_PRESS_MS);

  const onPointerUp = () => clearPressTimer();

  const onPointerMove = (moveEvent) => {
    if (
        Math.abs(moveEvent.clientX - startX) > MOVE_TOLERANCE
        || Math.abs(moveEvent.clientY - startY) > MOVE_TOLERANCE
    )
      clearPressTimer();
  };

  window.addEventListener('pointerup', onPointerUp, { once: true });
  window.addEventListener('pointercancel', onPointerUp, { once: true });
  window.addEventListener('pointermove', onPointerMove);

  pressCleanup = () => {
    window.removeEventListener('pointerup', onPointerUp);
    window.removeEventListener('pointercancel', onPointerUp);
    window.removeEventListener('pointermove', onPointerMove);
  };
}

async function pinApp(packageName) {
  await toggleDockPin(packageName);
}

async function unpinApp(packageName) {
  await toggleDockPin(packageName);
}

function onKeyDown(event) {
  if (event.key === 'Escape' && editOpen.value)
    closeEdit();
}

onBeforeUnmount(() => {
  clearTooltipTimers();
  clearPressTimer();
  tooltip.value = null;
  window.removeEventListener('keydown', onKeyDown);
});

onMounted(() => {
  window.addEventListener('keydown', onKeyDown);

  setTimeout(() => {
    size.value += 1;
    isShow.value = true;
    nextTick(() => remeasure());
  }, 210);
});
</script>
