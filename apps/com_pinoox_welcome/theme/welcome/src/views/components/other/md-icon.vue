<template>
  <svg-icon type="mdi" :path="icon"></svg-icon>
</template>

<script>
import SvgIcon from '@jamescoyle/vue-icon';
import {mdiAccount} from '@mdi/js';

export default {
  name: 'md-icon',
  components: {
    SvgIcon
  },
  props: {
    icon: {
      type: String
    }
  },
  data(){
    return {
      path : mdiAccount
    }
  }
}
</script>
