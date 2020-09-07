<template>
    <section>
        <div class="windows-page">
            <router-view></router-view>
        </div>
    </section>
</template>

<script>
    import {mapState} from "vuex";

    export default {
        computed: {
            ...mapState(['apps']),
            marketIcon() {
                return this.apps['com_pinoox_market'].icon;
            }
        },
        watch: {
            '$route': {
                handler() {
                    this.pushToTabs({
                        key: 'market-home',
                        label: 'market',
                        image: this.marketIcon,
                    });
                },
                immediate: true,
            }
        }
    }

</script>