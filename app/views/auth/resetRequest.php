<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Obnova hesla</title>
    <link rel="stylesheet" href="/mprojekt/public/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/navbar.php'; ?>
    <div class="container">
        <h1>Obnova hesla</h1>
        
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($_SESSION['message']); ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <form action="/mprojekt/public/auth/processResetRequest" method="POST">
            <label for="email">Zadejte svůj registrovaný e-mail:</label>
            <input type="email" id="email" name="email" required>
            <button type="submit" class="btn btn-primary">Odeslat odkaz pro obnovu hesla</button>
        </form>
    </div>
</body>
</html>
