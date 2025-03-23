<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Zadat ověřovací kód</title>
</head>
<body>
    <h1>Zadejte ověřovací kód</h1>
    <?php
    if (isset($_SESSION['message'])) {
        echo '<p style="color:red;">' . htmlspecialchars($_SESSION['message']) . '</p>';
        unset($_SESSION['message']);
    }
    ?>
    <form action="/mprojekt/public/auth/enterCode" method="POST">
        <label for="email">E-mail:</label><br>
        <input type="email" name="email" id="email" required><br><br>

        <label for="verification_code">Ověřovací kód:</label><br>
        <input type="text" name="verification_code" id="verification_code" required><br><br>

        <button type="submit">Ověřit</button>
    </form>
</body>
</html>
