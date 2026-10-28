<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$oid = (int)($_GET['id'] ?? 0);

if ($oid < 1) {
    die('Invalid order ID.');
}

// Fetch Order details
$st = mysqli_prepare($conn, "SELECT o.order_id, o.order_date, o.total_amount, c.customer_name 
                             FROM orders o 
                             JOIN customers c ON o.customer_id = c.customer_id 
                             WHERE o.order_id = ?");
mysqli_stmt_bind_param($st, 'i', $oid);
mysqli_stmt_execute($st);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

if (!$order) {
    die('Order not found.');
}

// Fetch Order Items
$st_items = mysqli_prepare($conn, "SELECT oi.quantity, oi.unit_price, p.product_name 
                                   FROM order_items oi 
                                   JOIN products p ON oi.product_id = p.product_id 
                                   WHERE oi.order_id = ?");
mysqli_stmt_bind_param($st_items, 'i', $oid);
mysqli_stmt_execute($st_items);
$items = mysqli_stmt_get_result($st_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Slip #<?= $oid ?> – Cup of Joe-rnal</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #f7efe9; display: flex; justify-content: center; padding: 40px 10px; }
        .slip-card { background: #fff; width: 340px; padding: 24px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .center { text-align: center; }
        .line { border-bottom: 2px dashed #888; margin: 12px 0; }
        .row { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px; }
        .total { font-weight: bold; font-size: 1.1rem; margin-top: 10px; }
        .btn-print { display: block; width: 100%; padding: 10px; background: #5d4037; color: #fff; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 16px; text-align: center; text-decoration: none; }
        @media print {
            body { background: #fff; padding: 0; }
            .slip-card { box-shadow: none; width: 100%; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

<div class="slip-card">
    <div class="center">
        <img src="assets/logo-sm.png" alt="" style="width:70px;height:70px;border-radius:50%;margin-bottom:6px;">
        <h2 style="margin:0;font-family:sans-serif;color:#5d4037;">Cup of Joe-rnal</h2>
        <p style="margin:4px 0;font-size:0.8rem;">ORDER SLIP (UNPAID)</p>
        <p style="margin:0;font-size:0.75rem;">Present to cashier for payment</p>
    </div>

    <div class="line"></div>

    <div class="row"><span>Order ID:</span><b>#<?= $order['order_id'] ?></b></div>
    <div class="row"><span>Date:</span><span><?= $order['order_date'] ?></span></div>
    <div class="row"><span>Customer:</span><span><?= $e($order['customer_name']) ?></span></div>

    <div class="line"></div>

    <?php while ($it = mysqli_fetch_assoc($items)): ?>
        <div class="row">
            <span><?= (int)$it['quantity'] ?>x <?= $e($it['product_name']) ?></span>
            <span>₱<?= number_format($it['quantity'] * $it['unit_price'], 2) ?></span>
        </div>
    <?php endwhile; ?>

    <div class="line"></div>

    <div class="row total">
        <span>TOTAL:</span>
        <span>₱<?= number_format($order['total_amount'], 2) ?></span>
    </div>

    <button class="btn-print" onclick="window.print()">🖨️ Print Slip</button>
</div>

</body>
</html>