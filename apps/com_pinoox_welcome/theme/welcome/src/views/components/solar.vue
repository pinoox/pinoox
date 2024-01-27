<template>
  <div class="solar-system">
    <div id="pinoox">
      <img src="@/assets/media/circle-pinoox.png" alt="pinoox">
    </div>

    <div v-for="planet in planets" :key="planet.name">
      <div :class="`orbit ${planet.name}-orbit`"></div>
      <div :class="`${planet.name}-spin`">
        <div :id="planet.name">
          <img :src="planet.imageSrc" :alt="planet.name">
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import circleRedImage from "@/assets/media/circle-red.png";
import circlePurpleImage from "@/assets/media/circle-purple.png";
import circleOrangeImage from "@/assets/media/circle-orange.png";
import circleGreenImage from "@/assets/media/circle-green.png";

export default {
  data() {
    return {
      planets: [
        {
          name: "mercury",
          imageSrc: circleRedImage
        },
        {
          name: "venus",
          imageSrc: circlePurpleImage
        },
        {
          name: "earth",
          imageSrc: circleOrangeImage
        },
        {
          name: "mars",
          imageSrc: circleGreenImage
        }
      ]
    };
  }
};
</script>