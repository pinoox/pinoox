<template>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="page">
                    <h1 class="title">{{ LANG.install.agreement }}</h1>
                    <div class="box bg-w">
                        <div ref="rulesScroller" class="rules-scroll">
                            <ul class="rules" v-html="agreementHtml"></ul>
                        </div>
                    </div>

                    <div class="acceptance">
                        <div class="pretty p-svg p-round p-jelly">
                            <input type="checkbox" v-model="isAgree" name="agree"/>
                            <div class="state p-success">
                                <div class="svg">
                                    <Icon name="check"/>
                                </div>
                                <label></label>
                            </div>
                        </div>
                        <p @click="isAgree = !isAgree" class="text">{{ LANG.install.rules_agree }}</p>
                    </div>
                    <div class="page-actions">
                        <span @click="prev()" class="btn btn-outline-light pin-btn">{{ LANG.install.back }}</span>
                        <button @click="next()" class="btn btn-light pin-btn" :disabled="!isAgree">
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
        agreementHtml.value = typeof data === 'string' ? data : ''
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
