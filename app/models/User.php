<?php
require_once __DIR__ . '/../../config/database.php';

class User {
    /**
     * Get all users from the database
     */
    public static function getAllUsers() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT * FROM users ORDER BY id");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error getting all users: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user by ID
     */
    public static function getUserById($id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error getting user by ID: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new user
     */
    public static function createUser($firstName, $lastName, $email, $password, $role = 'user') {
        global $pdo;
        try {
            // First check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Tento email je již registrován.'];
            }
            
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (:first_name, :last_name, :email, :password, :role)");
            $stmt->execute([
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':role' => $role
            ]);
            
            return ['success' => true, 'id' => $pdo->lastInsertId()];
        } catch (PDOException $e) {
            error_log('Error creating user: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Databázová chyba: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update an existing user
     */
    public static function updateUser($userId, $data) {
        global $pdo;
        try {
            $fields = [];
            $params = [':id' => $userId];
            
            // Add fields to update
            if (isset($data['first_name'])) {
                $fields[] = "first_name = :first_name";
                $params[':first_name'] = $data['first_name'];
            }
            
            if (isset($data['last_name'])) {
                $fields[] = "last_name = :last_name";
                $params[':last_name'] = $data['last_name'];
            }
            
            if (isset($data['email'])) {
                // Check if email already exists for another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :check_email AND id <> :check_id");
                $stmt->execute([':check_email' => $data['email'], ':check_id' => $userId]);
                
                if ($stmt->rowCount() > 0) {
                    return ['success' => false, 'message' => 'Tento email je již používán jiným uživatelem.'];
                }
                
                $fields[] = "email = :email";
                $params[':email'] = $data['email'];
            }
            
            if (isset($data['role'])) {
                $fields[] = "role = :role";
                $params[':role'] = $data['role'];
            }
            
            if (!empty($data['password'])) {
                $fields[] = "password = :password";
                $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            // If we have fields to update
            if (!empty($fields)) {
                $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                return ['success' => true];
            }
            
            return ['success' => false, 'message' => 'Žádná data k aktualizaci.'];
        } catch (PDOException $e) {
            error_log('Error updating user: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Databázová chyba: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete a user by ID
     */
    public static function deleteUser($id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            return ['success' => true];
        } catch (PDOException $e) {
            error_log('Error deleting user: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Databázová chyba: ' . $e->getMessage()];
        }
    }
}
