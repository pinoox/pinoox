<template>
  <div class="widgetPreview">
    <div class="widgetPreview__frame">
      <div v-if="$slots.header" class="widgetPreview__header">
        <slot name="header"/>
      </div>
      <div class="widgetPreview__body">
        <slot/>
      </div>
    </div>
  </div>
</template>
