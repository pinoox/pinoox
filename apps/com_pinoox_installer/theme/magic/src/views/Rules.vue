<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page">
                    <header class="page-header">
                        <h1 class="title">{{ LANG.install.agreement }}</h1>
                    </header>
                    <div class="box bg-w page-panel">
                        <div ref="rulesScroller" class="rules-scroll">
                            <ul class="rules" v-html="agreementHtml"></ul>
                        </div>
                    </div>

                    <div
                        class="acceptance"
                        :class="{'acceptance--checked': isAgree}"
                        role="checkbox"
                        :aria-checked="isAgree"
                        tabindex="0"
                        @click="isAgree = !isAgree"
                        @keydown.enter.prevent="isAgree = !isAgree"
                        @keydown.space.prevent="isAgree = !isAgree"
                    >
                        <div class="acceptance__control pretty p-svg p-round p-jelly" @click.stop>
                            <input type="checkbox" v-model="isAgree" name="agree"/>
                            <div class="state p-success">
                                <div class="svg">
                                    <Icon name="check"/>
                                </div>
                                <label></label>
                            </div>
                        </div>
                        <span class="acceptance__text">{{ LANG.install.rules_agree }}</span>
                    </div>
                    <div class="page-actions">
                        <button type="button" class="btn btn-outline-light pin-btn" @click="prev()">
                            {{ LANG.install.back }}
                        </button>
                        <button type="button" class="btn btn-light pin-btn" :disabled="!isAgree" @click="next()">
                            {{ LANG.install.continue }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {nextTick, onBeforeUnmount, onMounted, ref} from 'vue'
import {useRouter} from 'vue-router'
import {storeToRefs} from 'pinia'
import SimpleBar from 'simplebar'
import {installAPI} from '@api/install.js'
import {useInstallStore} from '@/stores/install.js'
import Icon from '@/components/icons/Icon.vue'

const emit = defineEmits(['update:steps'])

const router = useRouter()
const store = useInstallStore()
const {LANG} = storeToRefs(store)

const agreementHtml = ref('')
const isAgree = ref(false)
const rulesScroller = ref(null)
let rulesSimpleBar = null

onMounted(() => {
    emit('update:steps', [])
    installAPI.agreement().then(async (data) => {
        agreementHtml.value = data?.text ?? ''
        await nextTick()
        initRulesScroll()
    })
})

onBeforeUnmount(() => {
    rulesSimpleBar?.unMount()
    rulesSimpleBar = null
})

function initRulesScroll() {
    if (!rulesScroller.value) {
        return
    }

    rulesSimpleBar?.unMount()
    rulesSimpleBar = new SimpleBar(rulesScroller.value, {autoHide: false})
}

function next() {
    if (isAgree.value) {
        router.replace({name: 'prerequisites'})
    }
}

function prev() {
    router.replace({name: 'lang'})
}
</script>
