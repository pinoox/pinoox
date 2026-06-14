<template>
    <header class="site-header">
        <div class="container site-header__inner">
            <a class="site-header__brand" href="https://pinoox.com" target="_blank" rel="noopener noreferrer">
                <span class="site-header__logo-wrap">
                    <img src="@/assets/media/logo.png" alt="Pinoox" width="40" height="40">
                </span>
                <span class="site-header__name">{{ $t('copyright') }}</span>
            </a>
            <nav class="site-header__nav" :aria-label="$t('resources')">
                <a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/docs">{{ $t('documentation') }}</a>
                <a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/market">{{ $t('market') }}</a>
                <a target="_blank" rel="noopener noreferrer" href="https://pinoox.com/developer">{{ $t('developers') }}</a>
            </nav>
        </div>
    </header>
</template>
