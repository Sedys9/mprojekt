<?php
require_once __DIR__ . '/../../config/database.php';

class Product {
    public static function getAllProducts() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT * FROM products");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error getting all products: ' . $e->getMessage());
            return [];
        }
    }

    public static function getProductById($id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                error_log("No product found with ID: $id");
            }
            
            return $product;
        } catch (PDOException $e) {
            error_log('Error getting product by ID: ' . $e->getMessage());
            return false;
        }
    }

    public static function search($query) {
        global $pdo;
        $query = "%" . $query . "%";
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ?");
            $stmt->execute([$query, $query]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Chyba při vyhledávání produktů: " . $e->getMessage());
            return [];
        }
    }

    public static function getProductsByCategory($category) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE category = :category");
            $stmt->execute([':category' => $category]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error getting products by category: ' . $e->getMessage());
            return [];
        }
    }

    public static function createProduct($name, $description, $price, $category, $image, $sku = null, $stock = 0, $status = 'active', $featured = 0) {
        global $pdo;
        try {
            $sql = "INSERT INTO products (name, description, price, category, image";
            $params = [
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':category' => $category,
                ':image' => $image
            ];
            
            // Add optional fields if they exist
            if ($sku !== null) {
                $sql .= ", sku";
                $params[':sku'] = $sku;
            }
            
            $sql .= ", stock, status, featured) VALUES (:name, :description, :price, :category, :image";
            
            if ($sku !== null) {
                $sql .= ", :sku";
            }
            
            $sql .= ", :stock, :status, :featured)";
            $params[':stock'] = $stock;
            $params[':status'] = $status;
            $params[':featured'] = $featured;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Chyba při vkládání produktu: " . $e->getMessage());
            return false;
        }
    }    
}