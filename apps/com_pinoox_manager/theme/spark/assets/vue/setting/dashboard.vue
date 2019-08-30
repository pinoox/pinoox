<template>
    <div id="content" data-simplebar>
        <div class="header">
            <h1>{{LANG.manager.interface}}</h1>
        </div>
        <div class="page">
            <div class="config">
                <h2 class="title">{{LANG.setting.dashboard.choose_background_image}}</h2>
                <div class="pic-grid">
                    <img v-for="n in 5" :class="background == n ? 'active' : ''" @click="setBackground(n)"
                         :src="URL.THEME+'dist/images/backgrounds/'+n+'.jpg'">
                </div>
            </div>
            <div class="config">
                <h2 class="title">{{LANG.setting.dashboard.auto_lock_time}}</h2>
                <h3 class="description">{{LANG.setting.dashboard.auto_lock_help}}</h3>
                <select @change="changeLockTime()" v-model="lock_time" class="form-control col-md-3">
                    <option value="0">{{LANG.manager.disable}}</option>
                    <option value="10">10 {{LANG.manager.minute}}</option>
                    <option value="20">20 {{LANG.manager.minute}}</option>
                    <option value="30">30 {{LANG.manager.minute}}</option>
                    <option value="60">60 {{LANG.manager.minute}}</option>
                </select>
            </div>
            <div class="config">
                <h2 class="title">{{LANG.setting.dashboard.language}}</h2>
                <select class="form-control col-md-3" @change="setLang()" v-model="current_lang">
                    <option value="fa">فارسی</option>
                    <option value="en">English</option>
                </select>
            </div>
        </div>
    </div>
</template>


<script>
    import {mapState, mapMutations} from 'vuex';

    export default {
        computed: {
            ...mapState({
                background: state => state.options.background,
            }),
            lock_time: {
                get() {
                    return this.$store.state.options.lock_time;
                },
                set(val) {
                    this.$store.state.options.lock_time = val;
                }
            },
            clock: {
                get() {
                    return this.$store.state.clock;
                },
                set(val) {
                    this.$store.state.clock = val;
                }
            },
            current_lang: {
                get() {
                    return this.$store.state.options.lang;
                },
                set(val) {
                    this.$store.state.options.lang = val;
                }
            },
        },
        methods: {
            ...mapMutations(['updateDirections']),
            setBackground(name) {
                this.$http.get(this.URL.API + 'options/changeBackground/' + name).then((json) => {
                    this.$store.state.options.background = name;
                });
            },
            changeLockTime() {
                this.$http.get(this.URL.API + 'options/changeLockTime/' + this.lock_time).then((json) => {
                });
            },
            setLang() {
                this._loading = true;
                this.$http.get(this.URL.API + 'changeLang/' + this.current_lang).then((json) => {
                    this._loading = false;
                    this.updateDirections(json.data.direction);
                    this.LANG = json.data.lang;
                    this.clock = '';
                });
            }
        },
    }
</script>

