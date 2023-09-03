<template>
    <div class="widget widget-storage">
        <h2 class="title"><i class="fas fa-hdd"></i>{{LANG.widget.storage.server_storage}}</h2>
        <div class="content">
            <vm-progress type="circle" strokeColor="#de293a" track-color="rgba(127,127,127,0.3)" :percentage="info.percent"></vm-progress>
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
                });
            },
        }
    }
</script>