<?php

require_once __DIR__ . '/../../config/database.php';

class AuthController
{
    public function __construct()
    {
        date_default_timezone_set('Europe/Prague');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Registrace – uloží data do pending_registrations a odešle ověřovací kód na e-mail.
     * Poté přesměruje na stránku pro zadání kódu (/auth/enterCode).
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require __DIR__ . '/../views/auth/Register.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $firstName       = trim($_POST['first_name']);
            $lastName        = trim($_POST['last_name']);
            $email           = trim($_POST['email']);
            $password        = $_POST['password'];
            $passwordConfirm = $_POST['password_confirm'];

            // Validace vstupů
            if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($passwordConfirm)) {
                die('Všechna pole jsou povinná.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                die('Neplatný e-mail.');
            }
            if ($password !== $passwordConfirm) {
                die('Hesla se neshodují.');
            }
            if (strlen($password) < 6) {
                die('Heslo musí mít alespoň 6 znaků.');
            }

            global $pdo;

            // Zkontrolujeme, zda již existuje účet v users
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                die('Tento e-mail je již zaregistrován.');
            }

            // Zkontrolujeme, zda již probíhá registrace v pending_registrations
            $stmt = $pdo->prepare("SELECT id FROM pending_registrations WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                die('Registrace pro tento e-mail již probíhá. Zkontrolujte svůj e-mail pro ověřovací kód.');
            }

            // Zahashování hesla
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Vygenerujeme ověřovací kód – použijeme 4 bajty, tj. 8 hex znaků
            $verificationCode = bin2hex(random_bytes(4));

            // Nastavení časových údajů
            $created_at = date('Y-m-d H:i:s');
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // Uložení do dočasné tabulky pending_registrations
            $stmt = $pdo->prepare("
                INSERT INTO pending_registrations 
                (first_name, last_name, email, password, verification_code, created_at, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $verificationCode, $created_at, $expires_at]);

            // Odeslání ověřovacího e-mailu pomocí PHPMailer
            require_once __DIR__ . '/../../vendor/autoload.php';
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.seznam.cz';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'help.sportnutrition@seznam.cz';
                $mail->Password   = 'HeLpMatProjekt20254E';
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';
                $mail->Encoding   = 'base64';

                $mail->setFrom('help.sportnutrition@seznam.cz', 'SportNutrition');
                $mail->addAddress($email, $firstName . ' ' . $lastName);

                $mail->Subject = 'Váš ověřovací kód';
                $mail->Body    = 'Dobrý den, váš ověřovací kód je: ' . $verificationCode 
                                 . "\n\nZadejte jej na stránce /mprojekt/public/auth/enterCode, abyste dokončili registraci.";

                $mail->send();
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                echo "Chyba při odeslání e-mailu: {$mail->ErrorInfo}";
            }

            $_SESSION['message'] = "Ověřovací kód byl odeslán na váš e-mail. Zadejte jej pro dokončení registrace.";
            header("Location: /mprojekt/public/auth/enterCode");
            exit();
        }
    }

    /**
     * Stránka pro zadání ověřovacího kódu.
     * GET: zobrazí formulář pro zadání e-mailu a kódu.
     * POST: ověří kód a pokud je platný, vytvoří účet v tabulce users.
     */
    public function enterCode()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require __DIR__ . '/../views/auth/EnterCode.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $code  = trim($_POST['verification_code']);

            // Vyhledáme odpovídající záznam v pending_registrations, který ještě nevypršel
            $stmt = $pdo->prepare("
                SELECT * FROM pending_registrations 
                WHERE email = ? AND verification_code = ? AND expires_at > NOW()
            ");
            $stmt->execute([$email, $code]);
            $pending = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$pending) {
                $_SESSION['message'] = "Neplatný nebo vypršelý ověřovací kód.";
                header("Location: /mprojekt/public/auth/enterCode");
                exit();
            }

            // Vytvoříme účet v tabulce users
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, password, role, created_at)
                VALUES (?, ?, ?, ?, 'user', NOW())
            ");
            $stmt->execute([
                $pending['first_name'],
                $pending['last_name'],
                $pending['email'],
                $pending['password']
            ]);

            // Odstraníme záznam z pending_registrations
            $stmt = $pdo->prepare("DELETE FROM pending_registrations WHERE id = ?");
            $stmt->execute([$pending['id']]);

            $_SESSION['message'] = "Registrace dokončena. Nyní se můžete přihlásit.";
            header("Location: /mprojekt/public/auth/login");
            exit();
        }
    }

    /**
     * Přihlášení – zobrazení formuláře a zpracování přihlášení.
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require __DIR__ . '/../views/auth/Login.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            global $pdo;

            $email    = trim($_POST['email']);
            $password = $_POST['password'];

            // Vyhledáme uživatele v tabulce users
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                $_SESSION['message'] = "Nesprávné přihlašovací údaje.";
                header("Location: /mprojekt/public/auth/login");
                exit();
            }

            if (!password_verify($password, $user['password'])) {
                $_SESSION['message'] = "Nesprávné přihlašovací údaje.";
                header("Location: /mprojekt/public/auth/login");
                exit();
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['message'] = "Úspěšně přihlášen(a).";
            header("Location: /mprojekt/public/");
            exit();
        }
    }

    /**
     * Odhlášení – zruší session a přesměruje na hlavní stránku.
     */
    public function logout()
    {
        session_destroy();
        header("Location: /mprojekt/public/");
        exit();
    }

    /**
     * Zobrazení formuláře pro reset hesla.
     */
    public function resetRequestForm()
    {
        require __DIR__ . '/../views/auth/ResetRequest.php';
    }

    /**
     * Zpracování žádosti o reset hesla – vygeneruje token, odešle e-mail a uloží token do databáze.
     */
    public function processResetRequest()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);

            if (empty($email)) {
                $_SESSION['message'] = "Zadejte e-mail.";
                header("Location: /mprojekt/public/auth/resetRequest");
                exit();
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            $_SESSION['message'] = "Pokud zadaný e-mail existuje, obdržíte odkaz pro obnovu hesla.";

            if (!$user) {
                header("Location: /mprojekt/public/auth/resetRequest");
                exit();
            }

            $token = bin2hex(random_bytes(16));
            $created_at = date('Y-m-d H:i:s');
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("
                INSERT INTO password_resets (user_id, email, token, created_at, expires_at) 
                VALUES (:user_id, :email, :token, :created_at, :expires_at)
            ");
            $stmt->execute([
                ':user_id'    => $user['id'],
                ':email'      => $email,
                ':token'      => $token,
                ':created_at' => $created_at,
                ':expires_at' => $expires_at
            ]);

            require_once __DIR__ . '/../../vendor/autoload.php';
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.seznam.cz';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'help.sportnutrition@seznam.cz';
                $mail->Password   = 'HeLpMatProjekt20254E';
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';
                $mail->Encoding   = 'base64';
                $mail->setFrom('help.sportnutrition@seznam.cz', 'SportNutrition');
                $mail->addAddress($email, 'Adresát');

                $resetLink = "http://localhost/mprojekt/public/auth/resetPassword?token=" . $token;
                $mail->Subject = 'Obnova hesla';
                $mail->Body    = 'Klikněte na odkaz: ' . $resetLink;
                $mail->send();
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                echo "Chyba při odeslání e-mailu: {$mail->ErrorInfo}";
            }

            header("Location: /mprojekt/public/auth/resetRequest");
            exit();
        } else {
            header("Location: /mprojekt/public/auth/resetRequest");
            exit();
        }
    }

    /**
     * Zobrazení formuláře pro zadání nového hesla (reset hesla).
     */
    public function resetPasswordForm()
    {
        global $pdo;

        if (!isset($_GET['token'])) {
            $_SESSION['message'] = "Chybí token.";
            header("Location: /mprojekt/public/auth/resetRequest");
            exit();
        }

        $token = $_GET['token'];

        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW()");
        $stmt->execute([':token' => $token]);
        $resetRequest = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$resetRequest) {
            $_SESSION['message'] = "Token je neplatný nebo vypršel.";
            header("Location: /mprojekt/public/auth/resetRequest");
            exit();
        }

        require __DIR__ . '/../views/auth/ResetPassword.php';
    }

    /**
     * Zpracování zadání nového hesla – reset hesla.
     */
    public function processResetPassword()
    {
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['token'];
            $password = $_POST['password'];
            $passwordConfirm = $_POST['password_confirm'];

            if (empty($token) || empty($password) || empty($passwordConfirm)) {
                $_SESSION['message'] = "Vyplňte všechna pole.";
                header("Location: /mprojekt/public/auth/resetPassword?token=" . urlencode($token));
                exit();
            }

            if ($password !== $passwordConfirm) {
                $_SESSION['message'] = "Hesla se neshodují.";
                header("Location: /mprojekt/public/auth/resetPassword?token=" . urlencode($token));
                exit();
            }

            if (strlen($password) < 6) {
                $_SESSION['message'] = "Heslo musí mít alespoň 6 znaků.";
                header("Location: /mprojekt/public/auth/resetPassword?token=" . urlencode($token));
                exit();
            }

            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW()");
            $stmt->execute([':token' => $token]);
            $resetRequest = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$resetRequest) {
                $_SESSION['message'] = "Token je neplatný nebo vypršel.";
                header("Location: /mprojekt/public/auth/resetRequest");
                exit();
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute([
                ':password' => $hashedPassword,
                ':id'       => $resetRequest['user_id']
            ]);

            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
            $stmt->execute([':token' => $token]);

            $_SESSION['message'] = "Heslo bylo úspěšně resetováno. Nyní se můžete přihlásit.";
            header("Location: /mprojekt/public/auth/login");
            exit();
        } else {
            header("Location: /mprojekt/public/auth/resetRequest");
            exit();
        }
    }
}
