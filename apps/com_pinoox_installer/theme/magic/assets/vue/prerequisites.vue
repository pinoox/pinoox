<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page">
                    <h1 class="title">{{LANG.install.prerequisites}}</h1>
                    <h2 class="description">{{LANG.install.prerequisites_description}}</h2>
                    <div class="box">
                        <div v-if="isError" class="row col-sm-12">
                            <span class="badge badge-danger mt-2 mb-4">{{LANG.install.err_prerequisites}}</span>
                        </div>
                        <ul class="prerequisites" data-simplebar data-simplebar-auto-hide="false">
                            <li class="free_space"><span><i class="fa" :class="getIcon('free_space')"></i></span> {{LANG.install.prerequisites_required_space}}</li>
                            <li class="php"><span><i class="fa" :class="getIcon('php')"></i></span> {{LANG.install.prerequisites_php}}</li>
                            <li class="mod_rewrite"><span><i class="fa" :class="getIcon('mod_rewrite')"></i></span> {{LANG.install.prerequisites_mod_rewrite}}</li>
                            <li class="mysql"><span><i class="fa" :class="getIcon('mysql')"></i></span> {{LANG.install.prerequisites_mysql}}</li>
                        </ul>
                    </div>
                    <br>
                    <span @click="prev()" class="btn btn-outline-light pin-btn">{{LANG.install.back}}
                    </span>
                    <button @click="next()" class="btn btn-light pin-btn" :disabled="!isValid">
                        {{LANG.install.continue}}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                prerequisites: {
                    free_space: 'loading',
                    php: 'loading',
                    mod_rewrite: 'loading',
                    mysql: 'loading',
                },
            }
        },
        created() {
            this.$emit('update:steps', [true]);
            this.checkPrerequisites('free_space');
            setTimeout(() => {
                this.checkPrerequisites('php');
            }, 300);
            setTimeout(() => {
                this.checkPrerequisites('mod_rewrite');
            }, 600);
            setTimeout(() => {
                this.checkPrerequisites('mysql');
            }, 900);

        },
        computed: {
            isValid()
            {
                return (this.prerequisites.free_space === 'true') && (this.prerequisites.php === 'true') && (this.prerequisites.mod_rewrite === 'true') && (this.prerequisites.mysql === 'true');
            },
            isError()
            {
                return (this.prerequisites.free_space === 'false') || (this.prerequisites.php === 'false') || (this.prerequisites.mod_rewrite === 'false') || (this.prerequisites.mysql === 'false');
            }
        },
        props: {
            steps: {
                type: Array,
            },
        },
        methods: {
            checkPrerequisites(type) {
                this.$http.get(this.URL.API + 'checkPrerequisites/' + type).then((json) => {
                   if(json.data.status)
                       this.prerequisites[type] = 'true';
                   else
                       this.prerequisites[type] = 'false';
                });
            },
            next() {
                if (this.isValid)
                    this.$router.replace({name: 'db'});
            },
            prev() {
                this.$router.replace({name: 'rules'});
            },
            getIcon(type) {
                if (this.prerequisites[type] === 'false')
                    return 'fa-times';
                else if (this.prerequisites[type] === 'true')
                    return 'fa-check-circle';
                else
                    return 'fa-spinner';

            }
        }
    }
</script>