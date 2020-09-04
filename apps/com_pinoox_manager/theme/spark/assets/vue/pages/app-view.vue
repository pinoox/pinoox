<template>
    <section>
        <div class="windows-page">
            <div class="app-view">
                <iframe ref="demo" :src="URL.APP + 'app/'+package_name"></iframe>
            </div>

        </div>
    </section>
</template>

<script>
    import {mapState} from 'vuex';

    export default {

        props: ['package_name'],
        data() {
            return {
                address: null,
            }
        },
        computed:{
            ...mapState(['apps']),
            appName()
            {
                return this.apps[this.package_name].name;
            },
            appIcon()
            {
                return this.apps[this.package_name].icon;
            }
        },
        mounted() {
            this._loading = true;
            this.$refs.demo.onload = () => {
                this._loading = false;
            }
        },
        watch:{
            '$route':{
                handler(){
                    this.pushToTabs({
                        key: 'app-view:'+this.package_name,
                        label: this.appName,
                        image:this.appIcon,
                    });
                },
                immediate:true,
            }
        }

    }
</script>
