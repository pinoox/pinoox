// [parser-php]
const PINOOX = {

    // urls
    URL: {
        CURRENT: window.location.href,
        BASE: '<?php echo url("^"); ?>',
        APP: '<?php echo url(); ?>',
        API: '<?php echo url("^api/v1/"); ?>',
        SITE: '<?php echo url("~"); ?>',
        THEME: '<?php echo $_url; ?>',
        AVATAR: '<?php echo furl("resources/avatar.png"); ?>',
        APP_ICON: '<?php echo furl("resources/default.png"); ?>',
    },

    // list lang
    LANG: <?php echo @$_lang; ?>,
};