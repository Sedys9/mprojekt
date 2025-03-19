<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Musíte být přihlášeni, abyste mohli upravit svůj profil.";
    header("Location: /mprojekt/public/user/profile");
    exit();
}

require_once __DIR__ . '/../../../config/database.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['message'] = "Uživatel nenalezen.";
    header("Location: /mprojekt/public/user/profile");
    exit();
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravit profil</title>
    <link rel="stylesheet" href="/mprojekt/public/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/navbar.php'; ?>

    <div class="container">
        <header>
            <h1>Upravit profil</h1>
        </header>

        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($_SESSION['message']); ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <form action="/mprojekt/public/user/update" method="POST">
            <label for="first_name">Jméno:</label>
            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

            <label for="last_name">Příjmení:</label>
            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>

            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>

            <h3>Změna hesla (nepovinné)</h3>
            <label for="password">Nové heslo:</label>
            <input type="password" id="password" name="password" minlength="6">

            <label for="password_confirm">Potvrzení nového hesla:</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="6">

            <button type="submit" class="btn btn-primary">Uložit změny</button>
            <a href="/mprojekt/public/user/profile" class="btn btn-secondary">Zpět</a>
        </form>
    </div>
</body>
</html>
