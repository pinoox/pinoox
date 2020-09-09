<!doctype html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php lang('welcome.welcome_to_pinoox'); ?></title>
    <link rel="icon" href="<?php echo $_url; ?>assets/images/logo-64.png">
    <link rel="stylesheet" href="<?php echo $_url ?>assets/css/style.css">
</head>
<body class="<?php lang('welcome.direction'); ?>">

<div class="container">

    <div class="logo">
        <img src="<?php echo $_url ?>assets/images/pinoox.png" alt="logo">
        <h1><?php lang('welcome.welcome_to_pinoox'); ?></h1>
    </div>

    <div class="nav">
        <a target="_blank" href="https://www.pinoox.com">
            <img alt="web" src="<?php echo $_url; ?>assets/images/web.svg"/> <?php lang('welcome.website'); ?>
        </a>
        <a target="_blank" href="https://www.pinoox.com/blog">
            <img alt="blog" src="<?php echo $_url; ?>assets/images/blog.svg"/> <?php lang('welcome.blog'); ?></a>
        <a target="_blank" href="https://www.pinoox.com/answers">
            <img alt="question" src="<?php echo $_url; ?>assets/images/question.svg"/> <?php lang('welcome.answers'); ?></a>
        <a target="_blank" href="https://www.github.com/pinoox">
            <img alt="github" src="<?php echo $_url; ?>assets/images/github.svg"/> <?php lang('welcome.github'); ?></a>
    </div>

    <div class="nav">
        <a target="_blank" href="<?php echo url('~'); ?>manager" class="text-center pointer"><?php lang('welcome.manager_message'); ?></a>
    </div>
    <div class="footer">
        <?php lang('welcome.designed'); ?>
        <div class="heart">
            <img alt="heart" src="<?php echo $_url; ?>assets/images/heart.svg"/>
        </div>
        <?php lang('welcome.for_you'); ?>
    </div>
</div>

</body>
</html>