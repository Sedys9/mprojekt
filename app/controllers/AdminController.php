<?php
require_once __DIR__ . '/../models/Product.php';

class AdminController {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }        
        
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['message'] = 'Nemáte oprávnění pro přístup do admin panelu.';
            header('Location: /mprojekt/public/');
            exit();
        }
    }

    public function index() {
        require __DIR__ . '/../views/admin/index.php';
    }

    public function createProduct() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = (float)$_POST['price'];
            $category = trim($_POST['category']);
            $image = $_FILES['image'];
            
            // Get other fields if they exist
            $sku = isset($_POST['sku']) ? trim($_POST['sku']) : null;
            $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
            $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';
            $featured = isset($_POST['featured']) ? 1 : 0;

            if (empty($name) || empty($description) || $price <= 0 || empty($category) || empty($image)) {
                $_SESSION['message'] = 'Vyplňte všechna pole.';
                header('Location: /mprojekt/public/admin');
                exit();
            }

            // Only store the filename, not the full path
            $imageFilename = basename($image['name']);
            
            // Upload the file
            $uploadPath = __DIR__ . '/../../public/assets/images/' . $imageFilename;
            if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
                // File uploaded successfully - save only the filename to the database
                Product::createProduct($name, $description, $price, $category, $imageFilename, $sku, $stock, $status, $featured);
                
                $_SESSION['message'] = 'Produkt byl úspěšně přidán!';
            } else {
                $_SESSION['message'] = 'Chyba při nahrávání obrázku!';
            }
            
            header('Location: /mprojekt/public/admin');
            exit();
        }
    }

    public function delete() {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['message'] = 'Neplatné ID produktu.';
            header('Location: /mprojekt/public/admin');
            exit();
        }
    
        $productId = (int)$_GET['id'];
    
        global $pdo;
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute([':id' => $productId]);
    
            $_SESSION['message'] = 'Produkt byl úspěšně smazán.';
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Chyba při mazání produktu: ' . $e->getMessage();
        }
    
        header('Location: /mprojekt/public/admin');
        exit();
    }
    

    public function edit() {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['message'] = 'Neplatné ID produktu.';
            header('Location: /mprojekt/public/admin');
            exit();
        }
    
        $productId = (int)$_GET['id'];
        global $pdo;
    
        // Načtení informací o produktu
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$product) {
            $_SESSION['message'] = 'Produkt nebyl nalezen.';
            header('Location: /mprojekt/public/admin');
            exit();
        }
    
        require __DIR__ . '/../views/admin/edit.php';
    }

    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /mprojekt/public/admin');
            exit();
        }
    
        $productId = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $category = trim($_POST['category']);
    
        global $pdo;
    
        try {
            $stmt = $pdo->prepare("UPDATE products SET name = :name, description = :description, price = :price, category = :category WHERE id = :id");
            $stmt->execute([
                ':id' => $productId,
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':category' => $category
            ]);
    
            $_SESSION['message'] = 'Produkt byl úspěšně upraven.';
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Chyba při úpravě produktu: ' . $e->getMessage();
        }
    
        header('Location: /mprojekt/public/admin');
        exit();
    }

    // Add these methods for user management
    public function createUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $firstName = trim($_POST['first_name']);
            $lastName = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $role = trim($_POST['role']);
            
            if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
                $_SESSION['message'] = 'Všechna pole jsou povinná.';
                header('Location: /mprojekt/public/admin?tab=users&action=create');
                exit();
            }
            
            require_once __DIR__ . '/../models/User.php';
            $result = User::createUser($firstName, $lastName, $email, $password, $role);
            
            if ($result['success']) {
                $_SESSION['message'] = 'Uživatel byl úspěšně vytvořen.';
                header('Location: /mprojekt/public/admin?tab=users');
            } else {
                $_SESSION['message'] = $result['message'];
                header('Location: /mprojekt/public/admin?tab=users&action=create');
            }
            exit();
        }
    }

    public function updateUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)$_POST['user_id'];
            $data = [
                'first_name' => trim($_POST['first_name']),
                'last_name' => trim($_POST['last_name']),
                'email' => trim($_POST['email']),
                'role' => trim($_POST['role'])
            ];
            
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }
            
            require_once __DIR__ . '/../models/User.php';
            $result = User::updateUser($userId, $data);
            
            if ($result['success']) {
                $_SESSION['message'] = 'Údaje o uživateli byly úspěšně aktualizovány.';
            } else {
                $_SESSION['message'] = 'Chyba při aktualizaci uživatele: ' . $result['message'];
            }
            
            header('Location: /mprojekt/public/admin?tab=users');
            exit();
        }
    }

    public function deleteUser() {
        if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
            $_SESSION['message'] = 'Neplatné ID uživatele.';
            header('Location: /mprojekt/public/admin?tab=users');
            exit();
        }
        
        $userId = (int)$_GET['user_id'];
        
        // Prevent deleting yourself
        if ($userId === (int)$_SESSION['user_id']) {
            $_SESSION['message'] = 'Nemůžete smazat svůj vlastní účet.';
            header('Location: /mprojekt/public/admin?tab=users');
            exit();
        }
        
        require_once __DIR__ . '/../models/User.php';
        $result = User::deleteUser($userId);
        
        if ($result['success']) {
            $_SESSION['message'] = 'Uživatel byl úspěšně smazán.';
        } else {
            $_SESSION['message'] = 'Chyba při mazání uživatele: ' . $result['message'];
        }
        
        header('Location: /mprojekt/public/admin?tab=users');
        exit();
    }
    
}
