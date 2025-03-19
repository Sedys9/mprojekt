<?php
require_once __DIR__ . '/../../config/database.php';

class UserController {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in for protected methods
        if (!isset($_SESSION['user_id']) && $this->requiresAuth()) {
            $_SESSION['message'] = 'Pro přístup k profilu se musíte přihlásit.';
            header('Location: /mprojekt/app/views/auth/login');
            exit();
        }
    }
    
    private function requiresAuth() {
        // List of methods that require authentication
        $authMethods = ['profile', 'orders', 'settings'];
        
        // Get the calling method name
        $trace = debug_backtrace();
        if (isset($trace[1])) {
            $method = $trace[1]['function'];
            return in_array($method, $authMethods);
        }
        
        return false;
    }
    
    public function profile() {
        global $pdo;
        
        // Get user data from database
        try {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, created_at FROM users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $_SESSION['message'] = 'Uživatel nebyl nalezen.';
                header('Location: /mprojekt/public/');
                exit();
            }
            
            // Get user orders
            $ordersStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
            $ordersStmt->execute([':user_id' => $_SESSION['user_id']]);
            $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Load profile view
            require_once __DIR__ . '/../views/user/profile.php';
            
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Chyba při načítání profilu: ' . $e->getMessage();
            header('Location: /mprojekt/public/');
            exit();
        }
    }


    public function update() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: /mprojekt/public/user/profile");
        exit();
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = 'Nejste přihlášeni.';
        header("Location: /mprojekt/public/user/profile");
        exit();
    }

    global $pdo;
    $userId = $_SESSION['user_id'];
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $street = trim($_POST['street']);
    $city = trim($_POST['city']);
    $zip = trim($_POST['zip']);
    $password = $_POST['password'];
    $passwordConfirm = $_POST['password_confirm'];

    if (empty($firstName) || empty($lastName)) {
        $_SESSION['message'] = "Jméno a příjmení musí být vyplněno.";
        header("Location: /mprojekt/public/user/edit");
        exit();
    }

    // Příprava pro SQL dotaz
    $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, phone = :phone, street = :street, city = :city, zip = :zip";
    $params = [
        ':id' => $userId,
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':phone' => $phone,
        ':street' => $street,
        ':city' => $city,
        ':zip' => $zip
    ];

    // Kontrola změny hesla
    if (!empty($password) || !empty($passwordConfirm)) {
        if ($password !== $passwordConfirm) {
            $_SESSION['message'] = "Hesla se neshodují.";
            header("Location: /mprojekt/public/user/edit");
            exit();
        }

        if (strlen($password) < 6) {
            $_SESSION['message'] = "Heslo musí mít alespoň 6 znaků.";
            header("Location: /mprojekt/public/user/edit");
            exit();
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query .= ", password = :password";
        $params[':password'] = $hashedPassword;
    }

    $query .= " WHERE id = :id";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        $_SESSION['message'] = "Profil byl úspěšně aktualizován.";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Chyba při aktualizaci: " . $e->getMessage();
    }

    header("Location: /mprojekt/public/user/profile");
    exit();
}

    

    public function edit() {
        global $pdo;
    
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['message'] = 'Nejste přihlášeni.';
            header("Location: /mprojekt/public/user/profile");
            exit();
        }
    
        $userId = $_SESSION['user_id'];
    
        // Načtení informací o uživateli
        $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$user) {
            $_SESSION['message'] = "Uživatel nenalezen.";
            header("Location: /mprojekt/public/user/profile");
            exit();
        }
    
        // Načtení šablony edit.php
        require __DIR__ . '/../views/user/edit.php';
    }
    

    

    public function orders() {
        global $pdo;
        
        // Get user orders from database
        try {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Load orders view
            require_once __DIR__ . '/../views/user/orders.php';
            
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Chyba při načítání objednávek: ' . $e->getMessage();
            header('Location: /mprojekt/public/');
            exit();
        }
    }

    public function orderDetail() {
        global $pdo;
        
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['message'] = 'Neplatné ID objednávky.';
            header('Location: /mprojekt/public/user/orders');
            exit();
        }
        
        $orderId = (int)$_GET['id'];
        
        try {
            // Získat detail objednávky
            $stmt = $pdo->prepare("
                SELECT o.*, u.first_name, u.last_name, u.email 
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = :id AND (o.user_id = :user_id OR :is_admin = 1)
            ");
            
            // Zkontrolovat, zda je uživatel admin nebo vlastník objednávky
            $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' ? 1 : 0;
            
            $stmt->execute([
                ':id' => $orderId,
                ':user_id' => $_SESSION['user_id'] ?? 0,
                ':is_admin' => $isAdmin
            ]);
            
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                $_SESSION['message'] = 'Objednávka nenalezena nebo k ní nemáte přístup.';
                header('Location: /mprojekt/public/user/orders');
                exit();
            }
            
            // Získat položky objednávky
            $stmtItems = $pdo->prepare("
                SELECT oi.*, p.name, p.image, p.category 
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id
            ");
            $stmtItems->execute([':order_id' => $orderId]);
            $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            
            // Zobrazit detail objednávky
            require_once __DIR__ . '/../views/orders/detail.php';
            
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Chyba při načítání objednávky: ' . $e->getMessage();
            header('Location: /mprojekt/public/user/orders');
            exit();
        }
    }
}