<template>
    <div class="widget widget-storage">
        <h2 class="title"><i class="fas fa-hdd"></i>{{LANG.widget.storage.server_storage}}</h2>
        <div class="content">
            <div class="progressbar">
                <svg class="progress-ring"
                     width="120"
                     height="120">
                    <circle class="progress-ring__circle"
                            stroke="rgba(127,127,127,0.3)"
                            stroke-width="4"
                            fill="transparent"
                            r="52"
                            cx="60"
                            cy="60"/>
                    <circle id="usedStorage" class="progress-ring__circle"
                            stroke="#de293a"
                            stroke-width="4"
                            fill="transparent"
                            r="52"
                            cx="60"
                            cy="60"/>
                </svg>
                <div class="percent">{{info.percent}}%</div>

            </div>
            <div class="info">
                <ul>
                    <li><strong>{{LANG.widget.storage.capacity}}:</strong> <span>{{info.total}} {{LANG.widget.storage.GB}}</span></li>
                    <li><strong>{{LANG.widget.storage.free_space}}:</strong> <span>{{info.free}} {{LANG.widget.storage.GB}}</span></li>
                    <li><strong>{{LANG.widget.storage.use_space}}:</strong> <span>{{info.use}} {{LANG.widget.storage.GB}}</span></li>
                </ul>
            </div>
        </div>
    </div>
</template>
<script>

    export default {
        name: "clock",
        created() {
            this.load();
        },
        mounted()
        {
            if(this.info) {
                this.setProgress(this.info.percent);
            }
        },
        computed: {
            info: {
                get() {
                    return this.$store.state.storage;
                },
                set(val) {
                    this.$store.state.storage = val;
                }
            },
            isRun() {
                return this.$store.state.isRun;
            }
        },
        methods: {
            load() {
                if (this.info)
                    return;

                this.$http.get(this.URL.API + 'widget/storage').then((json) => {
                    this.info = json.data;
                    this.setProgress(this.info.percent);
                });
            },
            setProgress: function (percent) {
                {
                    let circle = document.getElementById('usedStorage');
                    let radius = circle.r.baseVal.value;
                    let circumference = radius * 2 * Math.PI;

                    circle.style.strokeDasharray = `${circumference} ${circumference}`;
                    circle.style.strokeDashoffset = circumference - percent / 100 * circumference;
                    this.info.percent = percent;
                }
            }
        }
    }
</script>