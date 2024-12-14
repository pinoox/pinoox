<template>
    <div class="footer">
        <div class="container">
            <div class="footer__group">
                <div class="footer__links">
                    <h3 class="footer__links-caption">{{ $t('getStarted') }}</h3>
                    <ul class="footer__links-list">
                        <li><a href="#">{{ $t('download') }}</a></li>
                        <li><a href="#">{{ $t('installAppTutorial') }}</a></li>
                        <li><a href="#">{{ $t('installTemplateTutorial') }}</a></li>
                        <li><a href="#">{{ $t('faq') }}</a></li>
                    </ul>
                </div>

                <div class="footer__links">
                    <h3 class="footer__links-caption">{{ $t('contactUs') }}</h3>
                    <ul class="footer__links-list">
                        <li><a href="#">{{ $t('github') }}</a></li>
                        <li><a href="#">{{ $t('discord') }}</a></li>
                        <li><a href="#">{{ $t('telegram') }}</a></li>
                        <li><a href="#">{{ $t('youtube') }}</a></li>
                        <li><a href="#">{{ $t('qAndA') }}</a></li>
                    </ul>
                </div>

                <div class="footer__links">
                    <h3 class="footer__links-caption">{{ $t('resources') }}</h3>
                    <ul class="footer__links-list">
                        <li><a href="#">{{ $t('documentation') }}</a></li>
                        <li><a href="#">{{ $t('blog') }}</a></li>
                        <li><a href="#">{{ $t('qAndA') }}</a></li>
                        <li><a href="#">{{ $t('market') }}</a></li>
                    </ul>
                </div>

                <div class="footer__links">
                    <h3 class="footer__links-caption">{{ $t('developers') }}</h3>
                    <ul class="footer__links-list">
                        <li><a href="#">{{ $t('becomeDeveloperLink') }}</a></li>
                        <li><a href="#">{{ $t('createFirstAppTutorial') }}</a></li>
                        <li><a href="#">{{ $t('publishAppConditions') }}</a></li>
                    </ul>
                </div>

            </div>


            <div class="footer__divider"></div>
            <div class="row">
                <div class="col-md-12">
                    <div class="footer__copyright">
                        <img class="footer__copyright-logo" src="@/assets/media/pinoox.png" alt="pinoox">
                        <div class="footer__copyright-brand">{{ $t('copyright') }}</div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</template>
<script setup>
</script>