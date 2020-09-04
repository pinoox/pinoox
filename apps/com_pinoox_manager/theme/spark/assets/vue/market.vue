<template>
    <div class="iframe">
        <div v-show="isLoading" class="pin-spinner"></div>
        <iframe v-show="!isLoading" frameBorder="0"
                id="iframe"
                width="100%"
                height="100%"
                @load="isLoading=false"
                src="http://127.0.0.1/hub/marketManager/">

        </iframe>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                isLoading: true,
            }
        },
        mounted() {
            window.addEventListener('message', function (downloadLink) {
            })
        }
    }
</script>