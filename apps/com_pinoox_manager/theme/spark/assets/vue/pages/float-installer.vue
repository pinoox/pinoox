<template>
    <div v-if="app!=null"
         id="float-installer"
         class="float-installer"
         :class="app==null ? '':'animated faster zoomInDown'">
        <div class="header">
            <span @click="close()" class="_close"><i class="fa fa-times"></i></span>
            <div class="header-content">
                <img :src="app.icon" class="app-icon">
                {{app.app_name}}
            </div>
        </div>
        <div class="content pretty-scroll">
            <div class="text"></div>
        </div>
        <div class="actions">
            <div class="btn-pin">{{LANG.manager.install}}</div>
            <div class="btn-pin">{{LANG.manager.cancel}}</div>
        </div>
    </div>
</template>

<script>
    import PlainDraggable from 'plain-draggable';

    export default {
        name: "FloatInstaller",
        props: ['app'],
        computed: {},
        data() {
            return {
                app: {}
            }
        },
        mounted() {
            let drag = document.getElementById('float-installer');
            new PlainDraggable(drag);
        },
        methods: {
            close() {
                this.$emit('close');
            },
        }
    }
</script>