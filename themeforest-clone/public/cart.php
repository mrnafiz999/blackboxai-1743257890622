<?php
require_once '../includes/header.php';
require_once '../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDBConnection();
$cartItems = [];
$subtotal = 0;
$total = 0;
$shipping = 5.00; // Flat rate shipping

// Get cart items from session
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
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
}

$total = $subtotal + $shipping;

// Handle remove item action
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $productId = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        header("Location: cart.php");
        exit();
    }
}

// Handle update quantities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $productId => $quantity) {
        $productId = (int)$productId;
        $quantity = (int)$quantity;
        
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
    }
    header("Location: cart.php");
    exit();
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Shopping Cart</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($cartItems)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                            <h4>Your cart is empty</h4>
                            <p class="text-muted">Browse our products and add some items to your cart</p>
                            <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="cart.php">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cartItems as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= htmlspecialchars($item['thumbnail_url'] ?? '../assets/images/default-product.jpg') ?>" 
                                                             class="img-thumbnail me-3" width="80" alt="<?= htmlspecialchars($item['title']) ?>">
                                                        <div>
                                                            <h5 class="mb-0"><?= htmlspecialchars($item['title']) ?></h5>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>$<?= number_format($item['price'], 2) ?></td>
                                                <td>
                                                    <input type="number" name="quantity[<?= $item['id'] ?>]" 
                                                           value="<?= $item['quantity'] ?>" min="1" class="form-control" style="width: 70px;">
                                                </td>
                                                <td>$<?= number_format($item['total'], 2) ?></td>
                                                <td>
                                                    <a href="cart.php?remove=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="products.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                                </a>
                                <button type="submit" name="update_cart" class="btn btn-primary">
                                    <i class="fas fa-sync-alt me-2"></i>Update Cart
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($cartItems)): ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Subtotal
                                <span>$<?= number_format($subtotal, 2) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Shipping
                                <span>$<?= number_format($shipping, 2) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center fw-bold">
                                Total
                                <span>$<?= number_format($total, 2) ?></span>
                            </li>
                        </ul>
                        <a href="checkout.php" class="btn btn-primary w-100">Proceed to Checkout</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
require_once '../includes/footer.php';
?>