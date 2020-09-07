<template>
    <router-view></router-view>
</template>

<script>
    export default {
        data() {
            return {
                sidebar: {
                    back: true,
                    menus: [
                        {
                            name: 'apps-home',
                            label: 'apps_list',
                            icon: 'fas fa-grip-horizontal',
                        },
                        {
                            name: 'apps-manual',
                            label: 'manual_installation',
                            icon: 'fas fa-archive',
                        },
                    ]
                }
            }
        }
    }
</script>
