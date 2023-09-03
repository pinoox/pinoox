<template>
    <div id="page">
        <div class="text-center animated zoomInDown">
            <h1 class="title">{{LANG.install.main_title}}</h1>
            <h2 class="description">{{LANG.install.main_description}}</h2>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <router-link tag="span" :to="{name:'rules'}" class="app animated bounceInDown">
                        <img class="icon" src="@img/logo/logo-256.png" alt="pinoox">
                        <span class="app-name">{{LANG.install.install_pinoox}}</span>
                        <span class="app-version">{{LANG.install.version}} <b>{{OPTIONS.version}}</b></span>
                    </router-link>
                </div>
            </div>
        </div>
    </div></template>

<script>
    export default {
    }
</script>