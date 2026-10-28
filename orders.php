<?php
include 'db.php';
include 'header.php';   // already blocks non-admins and starts the session

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);

// Optional filter: ?src=self (customer self-orders only) or ?src=staff
$src   = $_GET['src'] ?? 'all';
$where = $src === 'self'  ? 'WHERE o.employee_id IS NULL'
       : ($src === 'staff' ? 'WHERE o.employee_id IS NOT NULL' : '');

$res = mysqli_query($conn, "SELECT o.order_id, o.order_date, o.total_amount,
        c.customer_name,
        COALESCE(e.employee_name, 'Self-order') AS placed_via,
        (SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.product_name) SEPARATOR ', ')
           FROM order_items oi JOIN products p ON p.product_id = oi.product_id
          WHERE oi.order_id = o.order_id) AS items
    FROM orders o
    JOIN customers c ON c.customer_id = o.customer_id
    LEFT JOIN employees e ON e.employee_id = o.employee_id
    $where
    ORDER BY o.order_date DESC, o.order_id DESC
    LIMIT 100");
?>
<meta http-equiv="refresh" content="30"><!-- auto-refresh every 30s so new orders show up -->

<div class="sub" style="margin-bottom:6px;">Incoming &amp; recent orders</div>
<h2>Customer Orders</h2>

<div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap">
  <a class="btn <?= $src === 'all'   ? '' : 'alt' ?> sm" href="orders.php">All</a>
  <a class="btn <?= $src === 'self'  ? '' : 'alt' ?> sm" href="orders.php?src=self">Customer self-orders</a>
  <a class="btn <?= $src === 'staff' ? '' : 'alt' ?> sm" href="orders.php?src=staff">Cashier orders</a>
</div>

<div class="panel">
  <b>🧾 Latest 100 orders</b>
  <table>
    <thead><tr><th>Order</th><th>Date &amp; Time</th><th>Customer</th><th>Order</th><th>Placed Via</th><th>Total</th><th>Slip</th></tr></thead>
    <tbody>
    <?php if (mysqli_num_rows($res) > 0): while ($r = mysqli_fetch_assoc($res)): ?>
      <tr>
        <td>#<?= (int)$r['order_id'] ?></td>
        <td><?= $e(date('M j, Y h:i A', strtotime($r['order_date']))) ?></td>
        <td><?= $e($r['customer_name']) ?></td>
        <td><?= $e($r['items']) ?></td>
        <td><?= $r['placed_via'] === 'Self-order' ? '<span class="pill">Self-order</span>' : $e($r['placed_via']) ?></td>
        <td>₱<?= number_format($r['total_amount'], 2) ?></td>
        <td><a class="btn sm alt" href="order_slip.php?id=<?= (int)$r['order_id'] ?>" target="_blank">📄 Slip</a></td>
      </tr>
    <?php endwhile; else: ?>
      <tr><td colspan="7" class="empty">No orders yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

</main></div></body></html>