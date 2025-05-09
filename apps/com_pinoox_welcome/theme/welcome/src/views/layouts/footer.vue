<template>
    <div class="footer">
        <div class="container">
            <div class="footer__group">
                <div class="footer__links">
                    <h3 class="footer__links-caption">{{ $t('getStarted') }}</h3>
                    <ul class="footer__links-list">
                        <li><a target="_blank" href="https://pinoox.com/download">{{ $t('download') }}</a></li>
                        <li><a target="_blank" href="https://pinoox.com/docs">{{ $t('installAppTutorial') }}</a></li>
                        <li><a target="_blank" href="https://pinoox.com/docs">{{ $t('installTemplateTutorial') }}</a></li>
                        <li><a target="_blank" href="https://pinoox.com/faq">{{ $t('faq') }}</a></li>
                    </ul>
                </div>

                <div class="footer__links">
                    <h3 class="footer__links-caption">{{ $t('contactUs') }}</h3>
                    <ul class="footer__links-list">
                        <li><a target="_blank" href="https://github.com/pinoox/pinoox">{{ $t('github') }}</a></li>
                        <li><a target="_blank" href="https://t.me/pinoox">{{ $t('telegram') }}</a></li>
                        <li><a target="_blank" href="https://www.instagram.com/pinoox_fa/">{{ $t('instagram') }}</a></li>
                    </ul>
                </div>

                <div class="footer__links">
                    <h3 class="footer__links-caption">{{ $t('resources') }}</h3>
                    <ul class="footer__links-list">
                        <li><a target="_blank" href="https://pinoox.com/docs">{{ $t('documentation') }}</a></li>
                        <li><a target="_blank" href="https://pinoox.com/blog">{{ $t('blog') }}</a></li>
                        <li><a target="_blank" href="https://pinoox.com/market">{{ $t('market') }}</a></li>
                    </ul>
                </div>

                <div class="footer__links">
                    <h3 class="footer__links-caption">{{ $t('developers') }}</h3>
                    <ul class="footer__links-list">
                        <li><a target="_blank" href="https://pinoox.com/developer">{{ $t('becomeDeveloperLink') }}</a></li>
                        <li><a target="_blank" href="https://pinoox.com/developer">{{ $t('createFirstAppTutorial') }}</a></li>
                        <li><a target="_blank" href="https://pinoox.com/developer">{{ $t('publishAppConditions') }}</a></li>
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