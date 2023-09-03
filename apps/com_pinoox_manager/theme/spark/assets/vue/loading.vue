<template>
    <section>
        <div class="loading">
            <div class="spinner">
                <img class="logo" src="@img/logo/logo-256.png" alt="logo pinoox">
                <div class="double-bounce1"></div>
                <div class="double-bounce2"></div>
            </div>
            <h5 class="text" v-if="message.length>0">{{message}}</h5>
            <h5 class="text" v-else>{{LANG.manager.please_wait}}</h5>
        </div>
    </section>
</template>

<script>
    export default {
        props:{
            message: {
                type:String,
                default: '',
            },
        },
    };
</script>