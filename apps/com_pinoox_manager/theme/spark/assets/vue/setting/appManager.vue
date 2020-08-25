<template>
    <div class="content" data-simplebar>
        <div class="header">
           <div class="text">
               <h1>{{LANG.manager.app_manager_title}}</h1>
           </div>
        </div>
        <div class="page">
            <div class="app-manager">
                <router-view></router-view>
            </div>
        </div>
    </div>
</template>

<script>
    export default {

    }
</script>
