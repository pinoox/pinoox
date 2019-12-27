<template>
    <div class="market-home">
        <input v-model="keyword" @keyup="search()" type="text" class="pin-input"
               :placeholder="LANG.setting.market.search_placeholder">
        <div v-if="isLoading" class="pin-spinner"></div>
        <div v-else>
            <div class="apps-list">
                <div class="app-item" v-for="(app,index) in apps" @click="openApp(app)" :class="app.state">
                    <img :src="app.icon" alt="app.app_name">
                    <div class="name">{{app.app_name}}</div>
                    <i class="fa fa-download installState" v-if="app.state=='download'"></i>
                </div>
            </div>
        </div>

    </div>
</template>
<script>
    import {mapGetters, mapMutations, mapState} from 'vuex';

    export default {
        data() {
            return {
                isLoading: false,
                apps: [],
                keyword: '',
            }
        },
        created() {
            this.search();
        },
        computed: {
            ...mapGetters(['appsArray']),
        },
        methods: {
            openApp(app) {
                this.$router.push({path: `details/${app.package_name}`});
            },
            search() {
                this.isLoading = true;
                this._delay(() => {
                    this.$http.get(this.URL.API + 'market/getApps/' + this.keyword).then((json) => {
                        this.isLoading = false;
                        this.apps = json.data.items;
                        for (let i = 0; i < this.apps.length; i++) {
                            this.apps[i]['state'] = 'download';

                            //update base on installed apps
                            for (let j = 0; j < this.appsArray.length; j++) {
                                if (this.apps[i].package_name === this.appsArray[j].package_name) {
                                    this.apps[i]['state'] = 'installed';
                                    break;
                                }
                            }

                            //update state based on installing list
                            /* for (let k = 0; k < this.installList.length; k++) {
                                 if (this.apps[i].package_name === this.installList[k].package_name) {
                                     this.apps[i]['state'] = 'installing';
                                     break;
                                 }
                             }*/
                        }
                    });
                }, 250);

            },
        }
    }
</script>