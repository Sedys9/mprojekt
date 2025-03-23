<?php
// Připojení k databázi
require __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/constants.php';

// Proměnná pro uložení chybových zpráv
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validace
    if (empty($email) || empty($password)) {
        $error_message = 'Vyplňte všechny údaje.';
    } else {
        try {
            // Vyhledání uživatele podle e-mailu
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                $error_message = 'Neplatný e-mail nebo heslo.';
            } else {
                // Uložení přihlášení do session
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'];
                $_SESSION['user_role'] = $user['role']; // Uložení role uživatele
                header('Location: /mprojekt/public/');
                exit();
            }
        } catch (PDOException $e) {
            $error_message = 'Chyba při přihlášení: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení</title>
    <link rel="stylesheet" href="/mprojekt/public/assets/css/styles.css">
    <script>
        // Zobrazení chybové zprávy pomocí alert, pokud existuje
        <?php if(!empty($error_message)): ?>
        window.onload = function() {
            alert("<?php echo addslashes($error_message); ?>");
        }
        <?php endif; ?>
    </script>
</head>
<body>
    <?php include __DIR__ . '/../partials/navbar.php'; ?>
    <header>
        <h1>Přihlášení</h1>
    </header>
    <main>
        <form action="" method="POST">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>

            <label for="password">Heslo:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Přihlásit se</button>
            <p>Nemáte účet? <a href="/mprojekt/app/views/auth/register">Zaregistrujte se</a>.</p>

            <!-- Odkaz na obnovu hesla -->
            <p>Zapomněli jste heslo? 
                <a href="/mprojekt/public/auth/resetRequest">Obnovit heslo</a>.
            </p>
        </form>
    </main>
</body>
</html>
