<template>
    <div class="content" data-simplebar>
        <div class="header" v-if="$parent.selectedApp!=null">
            <div class="text">
                <h1>{{LANG.manager.app}} {{$parent.selectedApp.name}}</h1>
                <h2>{{$parent.selectedApp.description}}</h2>
            </div>
        </div>
        <div class="page">
        </div>
    </div>
</template>

<script>
    export default {
        props: ['package_name'],
        data() {
            return {
            }
        },
        methods: {
            initApp() {
                if (this.$parent.selectedApp == null) {
                    this.$router.replace({name: 'appManager-home'});
                }
                this.$emit('onUpdatePackageName', this.package_name);
            }
        },
        created() {
            this.initApp();
        },

    }
</script>
