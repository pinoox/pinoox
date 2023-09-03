<template>
  <div class="market-login">
    <div class="user">
      <img v-if="pinooxAuth.avatar_thumb!=null" class="user-image" :src="pinooxAuth.avatar_thumb"
           alt="user-profile-image">
      <h4>{{ pinooxAuth.fullname }}</h4>
      <h5>{{ pinooxAuth.email }}</h5>

      <a @click="logout()" class="logout">{{ LANG.user.logout_account }}</a>

    </div>

  </div>
</template>
<script>

export default {
  methods: {
    logout() {
      this.logoutPinooxAuth().then(() => {
        this.$router.replace('login').catch(()=>{});
      })
    }
  },
  created() {
  }

}
</script>