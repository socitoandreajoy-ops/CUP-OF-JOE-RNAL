<?php
include 'db.php';
include 'header.php';

// Block non-admin users
if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
    echo "<h3 style='color:red;'>Access Denied. Admin privileges required.</h3>";
    exit();
}

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);

// Only accept a real YYYY-MM-DD date
$day = $_GET['print_date'] ?? '';
$dt  = DateTime::createFromFormat('Y-m-d', $day);
$day = ($dt && $dt->format('Y-m-d') === $day) ? $day : '';
$today = date('Y-m-d');
?>

<?php if ($day): /* ============ SINGLE-DAY REPORT (this is what prints) ============ */

    // Orders for that day only
    $st = mysqli_prepare($conn, "SELECT o.order_id, o.order_date, c.customer_name,
            COALESCE(e.employee_name,'Self-order') AS employee_name, o.total_amount,
            (SELECT GROUP_CONCAT(CONCAT(oi.quantity,'x ',p.product_name) SEPARATOR ', ')
               FROM order_items oi JOIN products p ON p.product_id = oi.product_id
              WHERE oi.order_id = o.order_id) AS items
        FROM orders o
        JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN employees e ON o.employee_id = e.employee_id
        WHERE DATE(o.order_date) = ?
        ORDER BY o.order_date ASC");
    mysqli_stmt_bind_param($st, 's', $day);
    mysqli_stmt_execute($st);
    $orders = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);

    // Items sold that day only
    $st = mysqli_prepare($conn, "SELECT p.product_name, SUM(oi.quantity) AS qty, SUM(oi.quantity * oi.unit_price) AS amount
        FROM order_items oi
        JOIN orders o ON o.order_id = oi.order_id
        JOIN products p ON p.product_id = oi.product_id
        WHERE DATE(o.order_date) = ?
        GROUP BY p.product_id, p.product_name
        ORDER BY qty DESC, p.product_name");
    mysqli_stmt_bind_param($st, 's', $day);
    mysqli_stmt_execute($st);
    $sold = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);

    $total_sales = array_sum(array_column($orders, 'total_amount'));
    $total_items = array_sum(array_column($sold, 'qty'));
    $pretty = date('F j, Y (l)', strtotime($day));
?>
<div class="report-bar no-print">
    <h2>Daily Report – <?= $e($pretty) ?></h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn alt" href="reports.php">← All Dates</a>
        <button class="btn" onclick="window.print()">🖨️ Print This Day</button>
    </div>
</div>

<!-- Appears on paper only -->
<div class="report-head">
    <img src="assets/logo-sm.png" alt="">
    <h2>Cup of Joe-rnal</h2>
    <p><b>DAILY SALES REPORT</b> – <?= $e($pretty) ?></p>
    <p>Printed: <?= date('Y-m-d H:i') ?></p>
</div>

<div class="day-stats">
    <div class="stat dark"><small>Total Sales</small><div class="num">₱<?= number_format($total_sales, 2) ?></div></div>
    <div class="stat"><small>Orders</small><div class="num"><?= count($orders) ?></div></div>
    <div class="stat"><small>Items Sold</small><div class="num"><?= (int)$total_items ?></div></div>
</div>

<div class="panel">
    <b>🧾 Orders on <?= $e($day) ?></b>
    <table>
        <thead><tr><th>Order</th><th>Time</th><th>Customer</th><th>Items</th><th>Served By</th><th>Total</th></tr></thead>
        <tbody>
        <?php if ($orders): foreach ($orders as $r): ?>
            <tr>
                <td>#<?= (int)$r['order_id'] ?></td>
                <td><?= $e(date('h:i A', strtotime($r['order_date']))) ?></td>
                <td><?= $e($r['customer_name']) ?></td>
                <td><?= $e($r['items']) ?></td>
                <td><?= $e($r['employee_name']) ?></td>
                <td>₱<?= number_format($r['total_amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
            <tr><td colspan="5" style="text-align:right;font-weight:700">TOTAL</td><td style="font-weight:700">₱<?= number_format($total_sales, 2) ?></td></tr>
        <?php else: ?>
            <tr><td colspan="6" class="empty">No orders on this day.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="panel">
    <b>☕ Items Sold</b>
    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Amount</th></tr></thead>
        <tbody>
        <?php if ($sold): foreach ($sold as $r): ?>
            <tr><td><?= $e($r['product_name']) ?></td><td><?= (int)$r['qty'] ?></td><td>₱<?= number_format($r['amount'], 2) ?></td></tr>
        <?php endforeach; else: ?>
            <tr><td colspan="3" class="empty">Nothing sold on this day.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php else: /* ============ OVERVIEW (not meant for printing) ============ */ ?>

<div class="report-bar">
    <h2>Sales History &amp; Reports</h2>
    <form method="get">
        <input type="date" name="print_date" value="<?= $today ?>" max="<?= $today ?>" required>
        <button type="submit">📄 View / Print Day</button>
        <a class="btn alt" href="reports.php?print_date=<?= $today ?>">Today</a>
    </form>
</div>

<div class="panel">
<b>📊 Daily Sales Summary</b>
<table>
    <thead><tr><th>Date</th><th>Orders</th><th>Total Sales</th><th>Action</th></tr></thead>
    <tbody>
    <?php
    $daily_res = mysqli_query($conn, "SELECT DATE(order_date) AS day, COUNT(*) AS n, SUM(total_amount) AS sales
                                      FROM orders GROUP BY DATE(order_date) ORDER BY day DESC");
    if (mysqli_num_rows($daily_res) > 0) {
        while ($row = mysqli_fetch_assoc($daily_res)) {
            echo "<tr><td>" . $e($row['day']) . "</td><td>" . (int)$row['n'] . "</td>";
            echo "<td style='font-weight:bold;color:var(--ok);'>₱" . number_format($row['sales'], 2) . "</td>";
            echo "<td><a class='btn sm alt' href='reports.php?print_date=" . $e($row['day']) . "'>📄 Print Day Receipts</a></td></tr>";
        }
    } else {
        echo "<tr><td colspan='4' class='empty'>No daily sales data available.</td></tr>";
    }
    ?>
    </tbody>
</table>
</div>

<div class="panel">
<b>📜 Order History (latest 100)</b>
<table>
    <thead><tr><th>Order ID</th><th>Date &amp; Time</th><th>Customer</th><th>Served By</th><th>Total</th></tr></thead>
    <tbody>
    <?php
        $history_res = mysqli_query($conn, "SELECT o.order_id, o.order_date, c.customer_name,
            COALESCE(e.employee_name, o.placed_by_name, 'Self-order') AS employee_name, o.total_amount
        FROM orders o
        JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN employees e ON o.employee_id = e.employee_id
        ORDER BY o.order_date DESC LIMIT 100");
    if (mysqli_num_rows($history_res) > 0) {
        while ($row = mysqli_fetch_assoc($history_res)) {
            echo "<tr><td>#" . (int)$row['order_id'] . "</td><td>" . $e($row['order_date']) . "</td><td>" . $e($row['customer_name']) . "</td><td>" . $e($row['employee_name']) . "</td><td>₱" . number_format($row['total_amount'], 2) . "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='5' class='empty'>No orders yet.</td></tr>";
    }
    ?>
    </tbody>
</table>
</div>

<div class="panel">
<b>🏆 Top 5 Best-Selling Products (all time)</b>
<table>
    <thead><tr><th>Product Name</th><th>Total Quantity Sold</th></tr></thead>
    <tbody>
    <?php
    $best = mysqli_query($conn, "SELECT p.product_name, SUM(oi.quantity) AS sold
                                 FROM order_items oi JOIN products p ON oi.product_id = p.product_id
                                 GROUP BY p.product_id, p.product_name ORDER BY sold DESC LIMIT 5");
    if (mysqli_num_rows($best) > 0) {
        while ($row = mysqli_fetch_assoc($best)) {
            echo "<tr><td>" . $e($row['product_name']) . "</td><td style='font-weight:bold;'>" . (int)$row['sold'] . " units</td></tr>";
        }
    } else {
        echo "<tr><td colspan='2' class='empty'>No sales history available yet.</td></tr>";
    }
    ?>
    </tbody>
</table>
</div>

<?php endif; ?>

</main></div></body></html>