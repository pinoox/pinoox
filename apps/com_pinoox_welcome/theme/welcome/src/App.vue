<template>
  <RouterView />
  <Footer />
</template>

<script>
import { RouterLink, RouterView } from 'vue-router'
import Footer from '@/views/layouts/footer.vue';
export default {
  components :{
    RouterView,
    Footer
  },

}
</script>