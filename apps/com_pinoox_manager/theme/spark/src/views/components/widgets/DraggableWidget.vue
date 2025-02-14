<template>
  <div
      ref="widget"
      class="draggable-widget"
      @mousedown="startDrag"
      @touchstart="startDrag"
      :style="{ left: computedPosX, top: computedPosY }"
  >
    <div class="header">
      <slot name="header"></slot>
    </div>
    <div class="content">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
import {ref, onMounted, computed} from 'vue';

const props = defineProps({
  initialX: {type: [Number, String], default: "100px"},
  initialY: {type: [Number, String], default: "100px"}
});

const widget = ref(null);
let offsetX, offsetY;
const posX = ref(props.initialX);
const posY = ref(props.initialY);

const computedPosX = computed(() => typeof posX.value === 'string' ? posX.value : `${posX.value}px`);
const computedPosY = computed(() => typeof posY.value === 'string' ? posY.value : `${posY.value}px`);

onMounted(() => {
  if (widget.value) {
    widget.value.style.left = computedPosX.value;
    widget.value.style.top = computedPosY.value;
  }
});

const startDrag = (event) => {
  event.preventDefault();
  const clientX = event.touches ? event.touches[0].clientX : event.clientX;
  const clientY = event.touches ? event.touches[0].clientY : event.clientY;
  offsetX = clientX - widget.value.getBoundingClientRect().left;
  offsetY = clientY - widget.value.getBoundingClientRect().top;
  document.addEventListener('mousemove', onDrag);
  document.addEventListener('mouseup', stopDrag);
  document.addEventListener('touchmove', onDrag);
  document.addEventListener('touchend', stopDrag);
};

const onDrag = (event) => {
  const clientX = event.touches ? event.touches[0].clientX : event.clientX;
  const clientY = event.touches ? event.touches[0].clientY : event.clientY;
  const widgetRect = widget.value.getBoundingClientRect();
  const windowWidth = window.innerWidth;
  const windowHeight = window.innerHeight;

  let newX = clientX - offsetX;
  let newY = clientY - offsetY;

  // Boundaries
  newX = Math.max(0, Math.min(windowWidth - widgetRect.width, newX));
  newY = Math.max(0, Math.min(windowHeight - widgetRect.height, newY));

  posX.value = `${newX}px`;
  posY.value = `${newY}px`;
};

const stopDrag = () => {
  document.removeEventListener('mousemove', onDrag);
  document.removeEventListener('mouseup', stopDrag);
  document.removeEventListener('touchmove', onDrag);
  document.removeEventListener('touchend', stopDrag);
};
</script>