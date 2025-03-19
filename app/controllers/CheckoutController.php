<?php
require_once __DIR__ . '/../models/Product.php';

class CheckoutController
{
    public function index()
    {
        require __DIR__ . '/../views/checkout/index.php';
    }

    public function process()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $shippingAddress = trim($_POST['shipping_address']);
            $paymentMethod = trim($_POST['payment_method']);
            $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

            if (empty($cart)) {
                $_SESSION['message'] = 'Košík je prázdný, nelze vytvořit objednávku.';
                header('Location: /mprojekt/public/cart');
                exit();
            }

            // Kontrola, zda je uživatel přihlášen
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $firstName = null;
            $lastName = null;
            $email = null;
            $phone = null;

            if (!$userId) {
                // Nepřihlášený uživatel musí zadat své údaje
                $firstName = trim($_POST['first_name']);
                $lastName = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);

                if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
                    $_SESSION['message'] = 'Vyplňte všechny údaje pro objednávku.';
                    header('Location: /mprojekt/public/checkout');
                    exit();
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['message'] = 'Neplatná e-mailová adresa.';
                    header('Location: /mprojekt/public/checkout');
                    exit();
                }
            }

            // Výpočet celkové ceny
            $totalPrice = 0;
            $cartItems = [];
            foreach ($cart as $product_id => $quantity) {
                $product = Product::getProductById($product_id);
                if ($product) {
                    $itemTotal = $product['price'] * $quantity;
                    $totalPrice += $itemTotal;
                    $cartItems[] = [
                        'product_id' => $product_id,
                        'quantity'   => $quantity,
                        'price'      => $product['price']
                    ];
                }
            }

            global $pdo;

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO orders (user_id, first_name, last_name, email, phone, total_price, shipping_address, payment_method, created_at) 
                       VALUES (:user_id, :first_name, :last_name, :email, :phone, :total_price, :shipping_address, :payment_method, NOW())");

                    $stmt->execute([
                        ':user_id'         => $userId ? $userId : null,  // Pokud není přihlášen, nastaví se NULL
                        ':first_name'      => $firstName,
                        ':last_name'       => $lastName,
                        ':email'           => $email,
                        ':phone'           => $phone,
                        ':total_price'     => $totalPrice,
                        ':shipping_address'=> $shippingAddress,
                        ':payment_method'  => $paymentMethod
                    ]);

                $orderId = $pdo->lastInsertId();

                $stmtItems = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price)
                                            VALUES (:order_id, :product_id, :quantity, :price)");

                foreach ($cartItems as $item) {
                    $stmtItems->execute([
                        ':order_id'   => $orderId,
                        ':product_id' => $item['product_id'],
                        ':quantity'   => $item['quantity'],
                        ':price'      => $item['price']
                    ]);
                }

                $pdo->commit();

                unset($_SESSION['cart']);

                header("Location: /mprojekt/public/checkout/success?order_id=" . $orderId);
                exit();

            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['message'] = "Chyba při vytváření objednávky: " . $e->getMessage();
                header('Location: /mprojekt/public/cart');
                exit();
            }
        } else {
            header('Location: /mprojekt/public/checkout');
            exit();
        }
    }

    public function success()
    {
        require __DIR__ . '/../views/checkout/success.php';
    }
}
