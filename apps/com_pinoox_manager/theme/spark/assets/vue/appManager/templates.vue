<template>
    <div class="content" data-simplebar>
        <div class="header" v-if="app!=null">
            <div class="text">
                <h1>{{LANG.manager.app}} {{app.name}}</h1>
                <h2>{{app.description}}</h2>
            </div>
        </div>
        <div class="page">
            <div class="templates" v-if="templates!=null">
                <div class="item" v-for="(t,index) in templates">
                    <img class="thumb" :src="t.cover">
                    <div class="name">{{t.template_name}}</div>
                    <div class="actions">
                        <div @click="downloadTemplate(t)"
                             v-if="t.state==='download'"
                             class="btn-pin btn-success">
                            {{LANG.manager.download}}
                        </div>
                        <a :href="t.live_preview" v-if="t.live_preview!=null" class="btn-pin">{{LANG.manager.live_preview}}</a>
                    </div>
                </div>
            </div>
            <div v-else class="empty">
                <div>{{LANG.setting.appManager.empty_templates}}</div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['package_name'],
        computed: {
            app: {
                get() {
                    return this.$parent.app;
                },
                set(val) {
                    this.$parent.app = val;
                }
            },
        },
        data() {
            return {
                templates: null
            }
        },
        methods: {
            getTemplates() {
                this.$http.get(this.URL.API + 'template/get/' + this.package_name).then((json) => {
                    if (json.status === true)
                        this.templates = json.data;
                });
            }
        },
        created() {
            this.getTemplates();
        },

    }
</script>
