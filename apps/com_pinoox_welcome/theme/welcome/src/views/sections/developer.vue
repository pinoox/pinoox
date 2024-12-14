<template>
    <section class="section">
        <div class="section__developer">

            <div class="container">

                <div class="row">
                    <div class="col-md-12">
                        <div class="section__developer-title">
                            <h2>MOVE FORWARD</h2>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="section__developer-code">
                            <Highlighter class="section__developer-code--highlighter"></Highlighter>
                            <img class="section__developer-code--image" src="@/assets/media/mock.png" alt="mock">
                        </div>

                    </div>
                    <div class="col-md-6">
                        <div class="section__developer-content">
                            <div class="gradient-text">{{ $t('becomeDeveloper') }}</div>
                            <p class="text">{{ $t('developerDescription') }}</p>

                            <div class="btn btn-primary-outline">{{ $t('register') }}</div>
                        </div>



                    </div>
                </div>
            </div>

        </div>
    </section>
</template>

<script setup>
import Highlighter from "@/views/components/Highlighter.vue";
</script>