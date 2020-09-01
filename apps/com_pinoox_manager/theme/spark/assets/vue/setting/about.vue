<template>
    <div class="content" data-simplebar>
        <div class="header">
         <div class="text">
             <h1>{{LANG.manager.about_pinoox}}</h1>
         </div>
        </div>
        <div class="page">
            <div class="about">
                <div class="logo">
                    <img src="@img/logo/logo-256.png">
                    <div class="brand"> {{LANG.manager.pinoox}} <span class="ver">{{pinoox.client.version_name}}</span>
                    </div>
                </div>
                <div class="description">{{LANG.manager.pinoox_description}}</div>

                <div class="update">
                    <div v-if="isLoadingUpdate" class="btn-pin"> {{LANG.manager.updating}}
                        <div class="lds-ring">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>
                    <div v-else-if="isLoadingCheck" class="btn-pin"> {{LANG.manager.checking}}
                        <div class="lds-ring">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>
                    <div v-else @click="updatePinCore()" class="btn-pin"> {{LANG.manager.update}}</div>
                    <span v-if="this.pinoox.isNewVersion" class="message warning">{{LANG.manager.new_version}} {{pinoox.server.version_name}} {{LANG.manager.available}}</span>
                    <span v-else-if="isCheckUpdate" class="message">{{LANG.manager.your_pinoox_is_up_to_date}}</span>
                </div>
            </div>
        </div>
    </div>
</template>


<script>
    export default {
        data() {
            return {
                isCheckUpdate: false,
                isLoadingCheck: false,
            }
        },
        computed: {
            pinoox: {
                get() {
                    return this.$store.state.pinoox;
                },
                set(val) {
                    this.$store.state.pinoox = val;
                }
            },
            isLoadingUpdate: {
                get() {
                    return this.$store.state.isLoadingUpdate;
                },
                set(val) {
                    this.$store.state.isLoadingUpdate = val;
                }
            },
        },
        methods: {
            updatePinCore() {
                this.isLoadingCheck = true;

                this.$http.get(this.URL.API + 'update/checkVersion/force').then((json) => {
                    this.pinoox = json.data;
                    this.isCheckUpdate = true;
                    this.isLoadingUpdate = false;
                    this.isLoadingCheck = false;

                    if (this.pinoox.isNewVersion) {
                        this.notifyUpdate();
                    } else {
                        this._notify(this.LANG.manager.update, this.LANG.manager.your_pinoox_is_up_to_date, null);
                    }
                });
            },
            notifyUpdate() {
                this._notify(this.LANG.manager.update, this.LANG.manager.are_sure_update_pincore, null, [
                    {
                        text: this.LANG.manager.yes,
                        func: () => {
                            this.updateInstaller();
                        }
                    },
                    {
                        text:  this.LANG.manager.no,
                        func: () => {
                        }
                    }
                ]);
            },
            updateInstaller() {
                this.isLoadingUpdate = true;

                this.$http.get(this.URL.API + 'update/install').then((json) => {
                    this.isLoadingUpdate = false;

                    if (json.data.status) {
                        this.pinoox = json.data.result;
                        this._notify(this.LANG.manager.update,
                            this.LANG.manager.pinoox_successfully_update_to + this.pinoox.client.version_name + this.LANG.manager.updated, 'success');
                        this._redirect(this.URL.CURRENT,1);
                    } else {
                        this._notify(this.LANG.manager.update, this.LANG.manager.update_failed, 'danger');
                    }

                })
            }
        }
    }
</script>

<style>
    .lds-ring {
        display: inline-block;
        position: relative;
        width: 25px;
        height: 18px;
        padding: 0 10px 0 10px;
    }

    .lds-ring div {
        box-sizing: border-box;
        display: block;
        position: absolute;
        width: 20px;
        height: 20px;
        margin: 3px;
        border: 3px solid #fff;
        border-radius: 50%;
        animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        border-color: #fff transparent transparent transparent;
    }

    .lds-ring div:nth-child(1) {
        animation-delay: -0.45s;
    }

    .lds-ring div:nth-child(2) {
        animation-delay: -0.3s;
    }

    .lds-ring div:nth-child(3) {
        animation-delay: -0.15s;
    }

    @keyframes lds-ring {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

</style>