<template>
    <div class="content" data-simplebar>
        <div class="header" v-if="app!=null">
            <div class="text">
                <h1>{{LANG.manager.app}} {{app.name}}</h1>
                <h2>{{app.description}}</h2>
            </div>
        </div>
        <div class="page">
            <div class="app-manager" v-if="app!=null">
                <div class="info">
                    <div class="item">
                        <div class="label">{{LANG.manager.developer}}</div>
                        <div class="text">{{app.developer}}</div>
                    </div>
                    <div class="item">
                        <div class="label">{{LANG.manager.version}}</div>
                        <div class="text">{{app.version_code}}</div>
                    </div>
                    <div class="item">
                        <div class="label">{{LANG.manager.description}}</div>
                        <div class="text">{{app.description}}</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
    export default {
        props: ['package_name'],
        data() {
            return {}
        },
        computed: {
            app: {
                get() {
                    return !!this.$parent.selectedApp ? this.$parent.selectedApp : null;
                }
            }
        },
        methods: {
            initApp() {
                this.$emit('onUpdatePackageName', this.package_name);
            }
        },
        created() {
            this.initApp();
        },

    }
</script>
