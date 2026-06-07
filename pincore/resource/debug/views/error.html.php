<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?= $this->charset; ?>" />
    <meta name="robots" content="noindex,nofollow,noarchive" />
    <title>Pinoox · <?= $statusText; ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%232563eb'/%3E%3Ctext x='50%25' y='54%25' text-anchor='middle' font-size='28' fill='white' font-family='Arial,sans-serif'%3EP%3C/text%3E%3C/svg%3E" />
    <style><?= $this->include('assets/css/error.css'); ?></style>
</head>
<body>
<div class="container">
    <h1>Pinoox</h1>
    <h2>The server returned a "<?= $statusCode; ?> <?= $statusText; ?>".</h2>

    <p>
        Something went wrong while processing your request. If you are the site administrator,
        enable debug mode to inspect the full exception page.
    </p>
</div>
</body>
</html>

