<template>
    <section class="section">
        <div class="section__hero">
            <div class="container">

                <div class="circle"></div>
                <div class="curve"></div>

                <h2 class="title">{{ $t('title') }}</h2>
                <h2 class="gradient-text gradientAnim">{{ $t('welcome') }}</h2>

                <a class="btn btn-primary" href="">{{ $t('goToManager') }}</a>

                <p class="text">{{ $t('managerDescription') }}</p>

                <div class="image">
                    <img src="@/assets/media/hero.png" alt="manager app">
                </div>
            </div>

        </div>
    </section>
</template>