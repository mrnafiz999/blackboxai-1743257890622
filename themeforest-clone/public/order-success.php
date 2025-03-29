<?php
require_once '../includes/header.php';
require_once '../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if order ID is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: index.php");
    exit();
}

$orderId = (int)$_GET['order_id'];
$db = getDBConnection();

// Get order details
$orderStmt = $db->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$orderStmt->bind_param("ii", $orderId, $_SESSION['user_id']);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: index.php");
    exit();
}

// Get order items
$itemsStmt = $db->prepare("
    SELECT oi.*, p.title, p.thumbnail_url 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$orderItems = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0 text-center">Order Confirmation</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                        <h2>Thank You for Your Order!</h2>
                        <p class="lead">Your order has been placed successfully.</p>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Order Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Order Number:</strong> #<?= $order['id'] ?></p>
                                    <p><strong>Date:</strong> <?= date('F j, Y', strtotime($order['created_at'])) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                                    <p><strong>Status:</strong> <span class="badge bg-success">Completed</span></p>
                                </div>
                            </div>

                            <h6 class="mb-3">Order Items</h6>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orderItems as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= htmlspecialchars($item['thumbnail_url'] ?? '../assets/images/default-product.jpg') ?>" 
                                                             class="img-thumbnail me-3" width="60" alt="<?= htmlspecialchars($item['title']) ?>">
                                                        <div>
                                                            <h6 class="mb-0"><?= htmlspecialchars($item['title']) ?></h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>$<?= number_format($item['price'], 2) ?></td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Subtotal:</th>
                                            <td>$<?= number_format($order['total_amount'] - 5, 2) ?></td>
                                        </tr>
                                        <tr>
                                            <th colspan="3" class="text-end">Shipping:</th>
                                            <td>$5.00</td>
                                        </tr>
                                        <tr>
                                            <th colspan="3" class="text-end">Total:</th>
                                            <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Customer Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Billing Address</h6>
                                    <p>
                                        <?= htmlspecialchars($order['username']) ?><br>
                                        <?= htmlspecialchars($order['email']) ?><br>
                                        123 Main Street<br>
                                        New York, NY 10001<br>
                                        United States
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Shipping Address</h6>
                                    <p>
                                        <?= htmlspecialchars($order['username']) ?><br>
                                        123 Main Street<br>
                                        New York, NY 10001<br>
                                        United States
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center">
                        <p>An email confirmation has been sent to <strong><?= htmlspecialchars($order['email']) ?></strong></p>
                        <p>For any questions, please contact our <a href="#">customer support</a></p>
                        <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once '../includes/footer.php';
?>