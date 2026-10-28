<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';
include 'header.php';

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);

// Fetch Quick Stats
$count_products = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM products"))[0] ?? 0;
$count_cats     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM categories"))[0] ?? 0;
$count_custs    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM customers"))[0] ?? 0;
$count_lowstock = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM products WHERE stock_quantity <= 10"))[0] ?? 0;

// Fetch Low Stock Items (Threshold: 10 or less)
$low_stock_query = mysqli_query($conn, "SELECT product_name, stock_quantity FROM products WHERE stock_quantity <= 10 ORDER BY stock_quantity ASC LIMIT 5");

// Fetch Top Loyalty Customers
$loyalty_query = mysqli_query($conn, "SELECT customer_name, loyalty_points FROM customers ORDER BY loyalty_points DESC LIMIT 5");
?>

<div class="sub" style="margin-bottom:6px;">Good Afternoon, <?= $e($_SESSION['username'] ?? 'Administrator') ?></div>
<h2>Welcome to Cup of Joe-rnal</h2>

<!-- Stat Cards Row -->
<div class="stats">
    <div class="stat">
        <small>Products</small>
        <div class="num"><?= (int)$count_products ?></div>
    </div>
    <div class="stat">
        <small>Categories</small>
        <div class="num"><?= (int)$count_cats ?></div>
    </div>
    <div class="stat">
        <small>Customers</small>
        <div class="num"><?= (int)$count_custs ?></div>
    </div>
    <div class="stat dark">
        <small>Low Stock Items</small>
        <div class="num"><?= (int)$count_lowstock ?></div>
    </div>
</div>

<!-- Hero Banner -->
<div class="hero">
    <div>
        <h3>Smart Brewing for Every Day</h3>
        <p>Manage products, stock, customers, and orders seamlessly in one place.</p>
        <a href="new_order.php" class="btn" style="margin-top:12px;">+ New Order</a>
    </div>
    <div class="cup">
        <small style="opacity:0.85;">EST. 2026</small>
        <b>Cup of Joe-rnal</b>
        <span style="font-size:0.85rem;opacity:0.9;">Admin Dashboard</span>
    </div>
</div>

<!-- Two-Column Grid for Insights -->
<div class="grid2">
    <!-- Low Stock Alert Panel -->
    <div class="panel">
        <b>⚠️ Low Stock Items</b>
        <?php if (mysqli_num_rows($low_stock_query) > 0): ?>
            <?php while ($item = mysqli_fetch_assoc($low_stock_query)): ?>
                <div class="row">
                    <span><b><?= $e($item['product_name']) ?></b></span>
                    <span class="bad"><?= (int)$item['stock_quantity'] ?> left</span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="sub" style="padding:10px 0;">All products are well stocked!</p>
        <?php endif; ?>
    </div>

    <!-- Top Loyalty Customers Panel -->
    <div class="panel">
        <b>⭐ Top Loyalty Customers</b>
        <?php if (mysqli_num_rows($loyalty_query) > 0): ?>
            <?php while ($cust = mysqli_fetch_assoc($loyalty_query)): ?>
                <div class="row">
                    <span><?= $e($cust['customer_name']) ?></span>
                    <span class="pill">⭐ <?= (int)$cust['loyalty_points'] ?> pts</span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="sub" style="padding:10px 0;">No loyalty customers recorded yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>