<template>
    <div class="widget widget-clock">
        <time class="time">{{time}}</time>
        <span class="moment">{{clock.moment}}</span>
        <div class="date">{{clock.date}}</div>
        <div :style="progress" class="progress"></div>
    </div>
</template>

<script>

    import {mapMutations, mapState} from 'vuex';
    import moment from 'moment';

    export default {
        name: "clock",
        created() {
            this.load();
            this.update();
        },
        data() {
            return {
                time: 0,
                percent: 0,
            }
        },
        computed: {
            progress: function () {
                return 'width:' + this.percent + '%;'
            },
            clock: {
                get() {
                    return this.$store.state.clock;
                },
                set(val) {
                    this.$store.state.clock = val;
                }
            },
            ...mapState({
                isRun: 'isRun',
                timer: 'time',
                second:'second',
            }),
        },
        methods: {
            load() {
                if (!this.clock) {
                    this.$http.get(this.URL.API + 'widget/clock').then((json) => {
                        this.clock = json.data;
                        this.update();
                    });
                }
            },
            update() {
                if (!this.clock) return;
                let timestamp = this.clock.time + this.timer;
                let time = moment.unix(timestamp).format('h:mm');
                let s = moment.unix(timestamp).format('ss');
                this.percent = (parseInt(s) / 60) * 100;

                if (time !== this.time) {
                    this.time = time;
                }
            }
        },
        watch: {
            'timer': function () {
                this.update();
            }
        }
    }
</script>