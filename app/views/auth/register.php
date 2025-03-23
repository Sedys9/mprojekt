<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrace</title>
    <link rel="stylesheet" href="/mprojekt/public/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/navbar.php'; ?>
    <header>
        <h1>Registrace</h1>
    </header>
    <main>
        <?php
        if (!empty($_SESSION['message'])) {
            echo '<p style="color:red;">' . htmlspecialchars($_SESSION['message']) . '</p>';
            unset($_SESSION['message']);
        }
        ?>
        <form action="/mprojekt/public/auth/register" method="POST">
            <label for="first_name">Jméno:</label>
            <input type="text" id="first_name" name="first_name" required>

            <label for="last_name">Příjmení:</label>
            <input type="text" id="last_name" name="last_name" required>

            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Heslo:</label>
            <input type="password" id="password" name="password" required minlength="6">

            <label for="password_confirm">Heslo znovu:</label>
            <input type="password" id="password_confirm" name="password_confirm" required minlength="6">

            <button type="submit">Registrovat se</button>
            <p>Už máte účet? <a href="/mprojekt/public/auth/login">Přihlaste se</a>.</p>
        </form>
    </main>
</body>
</html>
