<template>
  <div class="dockbar" v-if="!!apps"
       v-show="isShow">
    <transition
        name="slide-up"
        appear
    >
      <dock-wrapper
          v-show="isShow"
          :size="size"
          :padding="padding"
          :gap="gap"
          :max-scale="maxScale"
          :max-range="maxRange"
          :disabled="disabled"
          :direction="direction"
          :position="position"
      >
        <dock-item v-for="item in systemApps" :key="item.name" @click="open(item)">
          <div class="item">
            <img v-if="item.image" :src="item.image" :alt="item.name" class="item-image"/>
            <Icon class="item-icon" v-else :is="item.icon"/>
            <span class="item-name" v-if="!!item?.name">{{ item.name }}</span>
          </div>
        </dock-item>

        <div class="dockbar__divider"></div>

        <dock-item v-for="item in apps" :key="item.name" @click="open(item)">
          <div class="item">
            <img v-if="item.image" :src="item.image" :alt="item.name" class="item-image"/>
            <Icon class="item-icon" v-else :is="item.icon"/>
            <span class="item-name" v-if="!!item?.name">{{ item.name }}</span>
          </div>
        </dock-item>
      </dock-wrapper>
    </transition>
  </div>
</template>

<script setup>
import 'dockbar';
import {ref, onMounted, defineProps} from 'vue';
import {RouterLink, useRouter} from 'vue-router';
import {saxIcon} from "../../../const/icons.js";

const props = defineProps({
  size: {type: Number, default: 55},
  padding: {type: Number, default: 12},
  gap: {type: Number, default: 12},
  maxScale: {type: Number, default: 1.5},
  maxRange: {type: Number, default: 200},
  disabled: {type: Boolean, default: false},
  direction: {type: String, default: 'horizontal'},
  position: {type: String, default: 'bottom'},
  apps: {
    type: Array,
    required: true,
    default: () => null
  },
  systemApps: {
    type: Array,
    required: true,
    default: () => [
      {route: '/control/profile', icon: saxIcon.control, image: null},
    ]
  },
});

const router = useRouter();
const isShow = ref(false);
const size = ref(props.size);

function open(item) {
  router.push(item.route)
}

onMounted(() => {
  setTimeout(() => {
    size.value += 1;
    isShow.value = true;
  }, 210);
});
</script>
