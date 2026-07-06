<template>
  <Header />
  <RouterView />
  <Footer />
</template>

<script>
import { RouterView } from 'vue-router'
import Header from '@/views/layouts/header.vue';
import Footer from '@/views/layouts/footer.vue';

export default {
  components: {
    RouterView,
    Header,
    Footer,
  },
}
</script>
