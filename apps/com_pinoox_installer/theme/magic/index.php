<!doctype html>
<html lang="fa">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="<?php echo $_url; ?>dist/<?php echo @$assets['css']; ?>">
    <link rel="icon" href="<?php echo $_url; ?>dist/images/logo-64.png">

    <title><?php lang('install.title_page'); ?></title>
</head>
<body class="<?php echo @$_direction; ?>">
<h1></h1>

<div id="app"></div>

<script src="<?php echo url(); ?>dist/pinoox.js"></script>
<script src="<?php echo $_url; ?>dist/<?php echo @$assets['js']; ?>"></script>

</body>
</html>