<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Reset hesla</title>
    <link rel="stylesheet" href="/mprojekt/public/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/navbar.php'; ?>
    <div class="container">
        <h1>Reset hesla</h1>
        
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($_SESSION['message']); ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <form action="/mprojekt/public/auth/processResetPassword" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">

            <label for="password">Nové heslo:</label>
            <input type="password" id="password" name="password" required minlength="6">
            
            <label for="password_confirm">Potvrzení nového hesla:</label>
            <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
            
            <button type="submit" class="btn btn-primary">Resetovat heslo</button>
        </form>
    </div>
</body>
</html>
