<template>
    <div id="page">
        <div class="text-center">
            <h1 class="title">{{LANG.install.select_lang}}</h1>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="box">
                        <ul class="lang" data-simplebar data-simplebar-auto-hide="false">
                            <li v-for="item in items">
                                <span v-if="item.lang !== OPTIONS.lang" @click="selectLang(item.lang)"><i
                                    class="flag-icon" :class="item.icon"></i> {{getLabel(item)}}</span>
                                <span v-else class="active"><i
                                        class="flag-icon" :class="item.icon"></i> {{getLabel(item)}}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <br/>
            <router-link tag="span" :to="{name:'setup'}" class="btn btn-light pin-btn pin-next">{{LANG.install.continue}}</router-link>
        </div>

    </div>
</template>

<script>
    export default {
        data() {
            return {
                items: [
                    {
                        label:'English - EN',
                        lang:'en',
                        icon:'flag-icon-gb',
                    },
                    {
                        label:'Persian - IR',
                        lang:'fa',
                        icon:'flag-icon-ir',
                    }
                ],
            }
        },
        methods: {
            selectLang(lang) {
                this.$parent.isLoading = true;
                this.$http.get(this.URL.API + 'changeLang/' + lang).then((json) => {
                    this.$parent.isLoading = false;
                    this.LANG = json.data.lang;
                    this.OPTIONS.lang = lang;
                    document.body.className = json.data.direction;
                });
            },
            getLabel(item)
            {
                return this.LANG.language[item.lang] !== undefined? this.LANG.language[item.lang] : item.label;
            }
        }
    }
</script>