<template>
  <div class="market-login">
        <span @click="$router.go(-1)" class="return pin-btn"><i
            class="fa fa-chevron-right"></i> {{ LANG.manager.return }}
        </span>
    <br>
    <br>
    <div class="message">
      <h2>{{ LANG.manager.required_login_for_download_app }}</h2>
    </div>

    <div class="form" @keyup.enter="login()">
      <img class="user-image" alt="pinoox" src="@img/logo/logo-256.png">
      <div class="user-input">
        <input v-model="params.email" type="text" :placeholder="LANG.user.username_or_email">
        <i class="fas fa-user"></i>
      </div>
      <div class="user-input">
        <input v-model="params.password" type="password" :placeholder="LANG.user.password">
        <i class="fas fa-lock"></i>
      </div>
      <br>
      <div v-if="isLoading" class="user-btn pin-loader"><i class="fa fa-spinner"></i></div>
      <a v-else @click="login()" class="user-btn">{{ LANG.user.login }}</a>
      <div class="message" style="margin-top: 10px;">
        <a href="https://www.pinoox.com/user/register" target="_blank">{{ LANG.manager.create_pinoox_account }}</a>
      </div>
      <br>
      <div class="message">
        <h2>{{LANG.manager.or}}</h2>
      </div>
      <a @click="connect()" class="user-btn">{{ LANG.user.connect_to_pinoox }} <i class="fa fa-plug"></i></a>
    </div>

  </div>
</template>
<script>

export default {
  data() {
    return {
      isLoading: false,
      isConnect: false,
      params: {
        email: null,
        password: null,
      }
    }
  },
  methods: {
    login() {
      this.isLoading = true;
      this.$http.post(this.URL.API + 'account/login', this.params).then((json) => {
        this.isLoading = false;
        if (json.data.status) {
          this.pinooxAuth = json.data.result;
          this.$router.go(-1);
        } else {
          this._notify(this.LANG.user.login_to_pinoox, json.data.result);
        }
      });
    },
    connect() {
      this.isConnect = true;
      this.$http.post(this.URL.API + 'account/connect').then((json) => {
        this.isConnect = false;
        if (json.data.status) {
          window.open('https://www.pinoox.com/connect?token=' + json.data.result, '_blank', 'location=yes,height=570,width=520,scrollbars=yes,status=yes');
          this.checkConnect();
        } else {
          this._notify(this.LANG.user.login_to_pinoox, json.data.result);
        }
      });
    },
    getData()
    {
        this.getPinooxAuth().then(()=>{
          this.$router.replace('account').catch(()=>{});
        });
    },
    checkConnect()
    {
      let vm = this;
      $(window).on("blur focus", function (e) {
        var prevType = $(this).data("prevType");

        if (prevType != e.type) {
          switch (e.type) {
            case "blur":
              break;
            case "focus":
                vm.getData();
              break;
          }
        }

        $(this).data("prevType", e.type);
      });

    }
  },
  created() {
  }
}
</script>