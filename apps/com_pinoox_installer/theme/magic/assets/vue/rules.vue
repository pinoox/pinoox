<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page">
                    <h1 class="title">{{LANG.install.agreement}}</h1>
                    <div class="box bg-w">
                        <ul ref="agreement" class="rules" data-simplebar data-simplebar-auto-hide="false">
                            <div v-html="agreement">
                                <li>
                                    <span class="caption">Introduction</span>
                                    <span class="text">Pinoox is an open-source platform published under the MIT license.</span>
                                    <br/></li>
                                <li>
                                    <br/>
                                    <span class="caption">privacy</span>
                                    <span class="text">we have published the source code for public access. You can check the security and flexibility of the system and use the system safely. The core of Pinoox is always free and open-source, so it doesn't cost you to get started.

                       <br/><br/>
                       to resolve issues and persistence, we receive your non-personal information such as web browser user agents, domain, language preference, and datetime. Pinoox uses this non-personal information to better understand users' needs and to improve the user experience.
                       </span>
                                </li>
                                <li>
                                    <br/>
                                    <span class="caption">we ask you to</span>
                                    <span class="text">
                                to keep you safe, make sure you get the Pinoox from the official Pinoox website from <a href="https://www.pinoox.com/" target="_blank">pinoox.com</a>
                        <br/><br/>
  to enhance the core and flexibility of the Pinoox platform, we invite you to provide us with feedback, comments, and suggestions, including bugs and issues that you may encounter when using Pinoox.
                    </span>
                                </li>
                                <li>
                                    <br/>
                                    <span class="caption">disclaimer</span>
                                    <span class="text">
Pinoox has no obligation whatsoever to how and where it is used by user, and any responsibility for its improper use lies with the user.
                        <br/><br/>
also, Pinoox does not commit any problems that may occur to your system, but that's not mean that Pinoox does not fix system problems
                       <br/><br/>
we are always striving to make Pinoox safer and more stable by providing continuous updates and tutorials                    </span>
                                </li>
                                <li><br/>
                                    for the latest news, updates and full details of this agreement, visit the official our website in <a href="https://www.pinoox.com/" target="_blank">pinoox.com</a>.
                                </li>
                                <li>Thanks for being with us. </li>
                            </div>
                        </ul>
                    </div>

                    <div class="acceptance">
                        <div class="pretty p-icon p-round p-jelly">
                            <input type="checkbox" v-model="isAgree" name="agree"/>
                            <div class="state p-success">
                                <i class="icon fa fa-check"></i>
                                <label></label>
                            </div>
                        </div>
                        <p @click="isAgree = !isAgree" class="text">{{LANG.install.rules_agree}}</p>

                    </div>
                    <br>
                    <span @click="prev()" class="btn btn-outline-light pin-btn">{{LANG.install.back}}
                    </span>
                    <button @click="next()" class="btn btn-light pin-btn" :disabled="!isAgree">
                        {{LANG.install.continue}}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        created() {
            this.$emit('update:steps', []);
            this.$parent.isLoading = true;
            this.$http.get(this.URL.API + 'agreement').then((response) => {
                this.$parent.isLoading = false;
                this.agreement = response.data;
            });
        },
        data() {
            return {
                agreement: '',
                isAgree: false,
            }
        },
        props: {
            steps: {
                type: Array,
            },
        },
        methods: {
            next() {
                if (this.isAgree)
                    this.$router.replace({name: 'prerequisites'});
            },
            prev() {
                this.$router.replace({name: 'lang'});
            }
        }
    }
</script>