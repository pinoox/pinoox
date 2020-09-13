<template>
    <div class="content" data-simplebar>
        <div class="header" v-if="app!=null">
            <div class="text">
                <h1>{{LANG.manager.app}} {{app.name}}</h1>
                <h2>{{app.description}}</h2>
            </div>
            <div class="action">
                <router-link :to="{name: 'market-details',params:{package_name:app.package_name,tab:'templates'}}" class="btn-header"><i class="fa fa-plus"></i> {{LANG.manager.add_template}}
                </router-link>
            </div>
        </div>
        <div class="page">
            <div class="templates" v-if="templates!=null" data-simplebar>
                <div class="item" v-for="(t,index) in templates">
                    <img class="thumb" :src="t.cover">
                    <div class="name">{{t.template_name}}</div>
                    <div class="actions">
                        <div v-if="!t.activate" @click="setTemplate(t)" class="btn-pin btn-primary">{{LANG.setting.appManager.activate_template}}</div>
                        <div v-if="!t.activate" @click="removeTemplate(t)" class="btn-pin btn-danger">{{LANG.manager.delete}}</div>
                        <div v-if="t.activate" class="btn-pin btn-success">{{LANG.manager.active}}</div>
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
                    if (json.data.status === true)
                        this.templates = json.data.result;
                });
            },
            removeTemplate(t) {
                this._notify(this.LANG.manager.alert, this.LANG.manager.are_you_sure_delete_template, null, [
                    {
                        text: this.LANG.manager.do_delete,
                        func: () => {
                            this._loading = true;
                            this.$http.post(this.URL.API + 'template/remove/' + this.app.package_name + '/' + t.folder).then((json) => {
                                this._loading = false;
                                this.getTemplates();
                            });
                        }
                    },
                    {
                        text: this.LANG.manager.no,
                        func: () => {
                        }
                    }
                ]);
            },
            setTemplate(t) {
                this.$http.get(this.URL.API + 'template/set/' + this.app.package_name + '/' + t.folder).then((json) => {
                    this.getTemplates();
                });
            }
        },
        created() {
            this.getTemplates();
        },

    }
</script>
