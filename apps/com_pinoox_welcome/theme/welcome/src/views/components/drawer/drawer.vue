<template>
  <div class="drawer" :class="{ 'is-open': isOpen, 'is-visible': isVisible }">
    <div class="drawer__overlay" :style="{ transitionDuration: `${speed}ms` }"></div>
    <div class="drawer__content" :class="direction"
         v-click-away="closeDrawer"
         :style="computeStyle">
      <slot></slot>
    </div>
  </div>
</template>

<script>
import {directive} from "vue3-click-away";

export default {
  directives: {
    ClickAway: directive,
  },
  props: {
    isOpen: {
      type: Boolean,
      required: false,
      default: false,
    },
    size: {
      type: String,
      required: false,
      default: "400px",
    },
    direction: {
      type: String,
      required: false,
      default: "right",//right,left,up,bottom
    },
    speed: {
      type: Number,
      required: false,
      default: 300,
    },
    backgroundColor: {
      type: String,
      required: false,
      default: "#fafafa",
    },
  },
  data() {
    return {
      isVisible: false,
      isTransitioning: false,
    };
  },
  computed: {
    computeStyle() {
      let style = {};
      if (this.direction === 'right' || this.direction === 'left') style.maxWidth = this.size;
      else if (this.direction === 'top' || this.direction === 'bottom') style.maxHeight = this.size;
      style.transitionDuration = `${this.speed}ms`;
      style.backgroundColor = this.backgroundColor;

      return style;
    }
  },
  watch: {
    isOpen(val) {
      this.isTransitioning = true;

      if (val) {
        this.toggleBackgroundScrolling(true);
        this.isVisible = true;
      } else {
        this.toggleBackgroundScrolling(false);
        setTimeout(() => (this.isVisible = false), this.speed);
      }

      setTimeout(() => (this.isTransitioning = false), this.speed);
    },
  },

  methods: {
    toggleBackgroundScrolling(enable) {
      const body = document.querySelector("body");

      body.style.overflow = enable ? "hidden" : null;
    },

    closeDrawer() {
      if (!this.isTransitioning) {
        this.$emit("close");
      }
    },
  },

  mounted() {
    this.isVisible = this.isOpen;
  },
};
</script>