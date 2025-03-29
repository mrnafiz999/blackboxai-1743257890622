<?php
require_once '../includes/header.php';
require_once '../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if cart is empty or user not logged in
if (empty($_SESSION['cart']) || !isset($_SESSION['user_id'])) {
    header("Location: cart.php");
    exit();
}

$db = getDBConnection();
$cartItems = [];
$subtotal = 0;
$shipping = 5.00; // Flat rate shipping
$total = 0;

// Get cart items from session
$cartIds = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($cartIds), '?'));
$types = str_repeat('i', count($cartIds));

$stmt = $db->prepare("
    SELECT p.id, p.title, p.price, p.thumbnail_url 
    FROM products p 
    WHERE p.id IN ($placeholders)
");
$stmt->bind_param($types, ...$cartIds);
$stmt->execute();
$result = $stmt->get_result();

while ($product = $result->fetch_assoc()) {
    $product['quantity'] = $_SESSION['cart'][$product['id']];
    $product['total'] = $product['price'] * $product['quantity'];
    $cartItems[] = $product;
    $subtotal += $product['total'];
}

$total = $subtotal + $shipping;

// Get user details
$userStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->bind_param("i", $_SESSION['user_id']);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process payment (simulated)
    $paymentMethod = $_POST['payment_method'];
    $cardNumber = $_POST['card_number'];
    $expiry = $_POST['expiry'];
    $cvv = $_POST['cvv'];
    
    // Validate inputs (simplified)
    if (empty($paymentMethod) || empty($cardNumber) || empty($expiry) || empty($cvv)) {
        $error = 'Please fill in all payment details';
    } else {
        // Create order in database
        $orderStmt = $db->prepare("
            INSERT INTO orders (user_id, total_amount, payment_method) 
            VALUES (?, ?, ?)
        ");
        $orderStmt->bind_param("ids", $_SESSION['user_id'], $total, $paymentMethod);
        $orderStmt->execute();
        $orderId = $db->insert_id;
        
        // Add order items
        foreach ($cartItems as $item) {
            $itemStmt = $db->prepare("
                INSERT INTO order_items (order_id, product_id, price, quantity) 
                VALUES (?, ?, ?, ?)
            ");
            $itemStmt->bind_param("iidi", $orderId, $item['id'], $item['price'], $item['quantity']);
            $itemStmt->execute();
        }
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        // Redirect to success page
        header("Location: order-success.php?order_id=$orderId");
        exit();
    }
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Checkout</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="checkout.php">
                        <h5 class="mb-3">Billing Information</h5>
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
                            </div>
                        </div>
                        
                        <h5 class="mb-3">Payment Method</h5>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="creditCard" value="Credit Card" checked>
                                <label class="form-check-label" for="creditCard">
                                    Credit Card
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="PayPal">
                                <label class="form-check-label" for="paypal">
                                    PayPal
                                </label>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" name="card_number" placeholder="1234 5678 9012 3456" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expiration Date</label>
                                <input type="text" class="form-control" name="expiry" placeholder="MM/YY" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CVV</label>
                                <input type="text" class="form-control" name="cvv" placeholder="123" required>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">Complete Order</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Your Items</h6>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($cartItems as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="my-0"><?= htmlspecialchars($item['title']) ?></h6>
                                    <small class="text-muted">Qty: <?= $item['quantity'] ?></small>
                                </div>
                                <span class="text-muted">$<?= number_format($item['total'], 2) ?></span>
                            </li>
                        <?php endforeach; ?>
                        
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Subtotal</span>
                            <strong>$<?= number_format($subtotal, 2) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Shipping</span>
                            <strong>$<?= number_format($shipping, 2) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total</span>
                            <strong>$<?= number_format($total, 2) ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once '../includes/footer.php';
?>