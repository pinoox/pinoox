<template>
    <footer class="footer" ref="footerRoot">
        <div class="container">
            <div class="footer__group">
                <details class="footer__block" open>
                    <summary class="footer__links-caption">{{ $t('getStarted') }}</summary>
                    <ul class="footer__links-list">
                        <li><a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/download">{{ $t('download') }}</a></li>
                        <li><a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/docs">{{ $t('installAppTutorial') }}</a></li>
                        <li><a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/docs">{{ $t('installTemplateTutorial') }}</a></li>
                        <li><a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/faq">{{ $t('faq') }}</a></li>
                    </ul>
                </details>

                <details class="footer__block" open>
                    <summary class="footer__links-caption">{{ $t('contactUs') }}</summary>
                    <ul class="footer__links-list">
                        <li><a target="_blank" rel="noopener noreferrer" href="https://github.com/pinoox/pinoox">{{ $t('github') }}</a></li>
                        <li><a target="_blank" rel="noopener noreferrer" href="https://t.me/pinoox">{{ $t('telegram') }}</a></li>
                        <li><a target="_blank" rel="noopener noreferrer" href="https://www.instagram.com/pinoox_fa/">{{ $t('instagram') }}</a></li>
                    </ul>
                </details>

                <details class="footer__block" open>
                    <summary class="footer__links-caption">{{ $t('resources') }}</summary>
                    <ul class="footer__links-list">
                        <li><a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/docs">{{ $t('documentation') }}</a></li>
                        <li><a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/blog">{{ $t('blog') }}</a></li>
                        <li><a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/market">{{ $t('market') }}</a></li>
                    </ul>
                </details>

                <details class="footer__block" open>
                    <summary class="footer__links-caption">{{ $t('developers') }}</summary>
                    <ul class="footer__links-list">
                        <li><a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/developer">{{ $t('becomeDeveloperLink') }}</a></li>
                        <li><a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/developer">{{ $t('createFirstAppTutorial') }}</a></li>
                        <li><a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/developer">{{ $t('publishAppConditions') }}</a></li>
                    </ul>
                </details>
            </div>

            <div class="footer__divider" role="separator"></div>

            <div class="footer__copyright">
                <span class="footer__copyright-logo-wrap">
                    <img class="footer__copyright-logo" src="@/assets/media/logo.png" alt="Pinoox" width="46" height="46">
                </span>
                <div class="footer__copyright-text">
                    <span class="footer__copyright-brand">{{ $t('copyright') }}</span>
                    <span class="footer__copyright-note">{{ $t('copyrightText') }}</span>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const footerRoot = ref(null);
const DESKTOP_MQ = '(min-width: 769px)';
let desktopMq = null;

function expandFooterOnDesktop() {
    if (!footerRoot.value || !window.matchMedia(DESKTOP_MQ).matches) {
        return;
    }

    footerRoot.value.querySelectorAll('.footer__block').forEach((block) => {
        block.setAttribute('open', '');
    });
}

function onDesktopMqChange(event) {
    if (event.matches) {
        expandFooterOnDesktop();
    }
}

onMounted(() => {
    desktopMq = window.matchMedia(DESKTOP_MQ);
    expandFooterOnDesktop();
    desktopMq.addEventListener('change', onDesktopMqChange);
});

onUnmounted(() => {
    desktopMq?.removeEventListener('change', onDesktopMqChange);
});
</script>
