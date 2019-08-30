<template>
    <div class="notifier" v-if="notifier.isShow" @click="closeBox"
         :class="['', (notifier.type ? notifier.type : ''), (notifier.isShow ? 'animated faster bounceIn'+animDirection : 'animated faster bounceOut'+animDirection)]">
        <div class="_close" @click="closeNotifier"><i class="fa fa-times"></i></div>
        <div class="title">{{notifier.title}}</div>
        <div class="message">{{notifier.message}}</div>
        <div v-if="notifier.actions!=null" class="actions">
            <div v-for="act in notifier.actions" @click="act.func" class="pin-btn">{{act.text}}</div>
        </div>
    </div>
</template>
<script>
    import {mapState} from 'vuex';

    export default {
        name: 'notifier',
        computed: {
            ...mapState(['notifier', 'animDirection']),
        },
        methods: {
            closeNotifier() {
                this.notifier.isShow = false;
            },
            closeBox(el) {
                if (this.notifier.actions != null && el.target.className === 'pin-btn')
                    this.closeNotifier();
            },
        }
    }
</script>