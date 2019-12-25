<!doctype html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php lang('welcome.welcome_to_pinoox'); ?></title>

    <link rel="stylesheet" href="<?php echo $_url ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $_url ?>assets/fonts/FontAwesome/css/all.css">
</head>
<body class="<?php echo lang('welcome.direction'); ?>">

<div class="container">

    <div class="logo">
        <img src="<?php echo $_url ?>assets/images/pinoox.png" alt="logo">
        <h1><?php lang('welcome.welcome_to_pinoox'); ?></h1>
    </div>

    <div class="nav">
        <a target="_blank" href="https://www.pinoox.com"><i
                    class="fas fa-desktop"></i> <?php lang('welcome.website'); ?>
        </a>
        <a target="_blank" href="https://www.pinoox.com/blog"><i
                    class="far fa-file"></i> <?php lang('welcome.blog'); ?></a>
        <a target="_blank" href="https://www.pinoox.com/answers"><i
                    class="fa fa-question"></i> <?php lang('welcome.forums'); ?></a>
        <a target="_blank" href="https://www.github.com/pinoox"><i
                    class="fab fa-github"></i> <?php lang('welcome.github'); ?></a>
    </div>

    <div class="nav">
        <a target="_blank" href="<?php echo url('~'); ?>manager" class="text-center pointer"><?php lang('welcome.manager_message'); ?></a>
    </div>
    <div class="footer">
        <?php lang('welcome.designed'); ?>
        <div class="heart">
            <i class="fa fa-heart"></i>
        </div>
        <?php lang('welcome.for_you'); ?>
    </div>
</div>

</body>
</html>