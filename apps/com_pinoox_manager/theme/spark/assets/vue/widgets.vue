<template>
    <div class="widgets">
        <clock></clock>
        <Storage></Storage>
    </div>
</template>

<script>
    import Clock from './widget/clock.vue';
    import Storage from './widget/storage.vue';
    import PlainDraggable from 'plain-draggable';

    export default {
        name: "widgets",
        components: {Clock,Storage},
        mounted() {
            let dragElements = document.getElementsByClassName('widget');
            dragElements.forEach((item) => {
                new PlainDraggable(item);
            });
        }
    }
</script>