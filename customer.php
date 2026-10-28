<?php
session_start();
if (strtolower($_SESSION['role'] ?? '') !== 'customer') { 
    header('Location: login.php'); 
    exit; 
}

include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$e   = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$cid = (int)($_SESSION['customer_id'] ?? 0);
$errors = []; 
$success = '';
$new_order_data = null;

// ---- Place an Order ----
if (isset($_POST['place_order'])) {
    $qtys = array_filter(array_map('intval', $_POST['qty'] ?? []), fn($q) => $q > 0);
    if ($cid < 1) {
        $errors[] = 'Your account is not linked to a customer record. Please ask staff for assistance.';
    }
    if (!$qtys) {
        $errors[] = 'Add at least one item to your order.';
    }

    if (!$errors) {
        mysqli_begin_transaction($conn);
        try {
            $st = mysqli_prepare($conn, "INSERT INTO orders (customer_id, employee_id) VALUES (?, NULL)");
            mysqli_stmt_bind_param($st, 'i', $cid); 
            mysqli_stmt_execute($st);
            $oid = mysqli_insert_id($conn); 
            $total = 0;
            $ordered_items = [];

            $get = mysqli_prepare($conn, "SELECT price, stock_quantity, product_name FROM products WHERE product_id=?");
            $ins = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?,?,?,?)");
            $upd = mysqli_prepare($conn, "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id=?");

            foreach ($qtys as $pid => $q) {
                $pid = (int)$pid;
                mysqli_stmt_bind_param($get, 'i', $pid); 
                mysqli_stmt_execute($get);
                $r = mysqli_fetch_assoc(mysqli_stmt_get_result($get));

                if (!$r) {
                    throw new Exception('One of the items is no longer available.');
                }
                if ($q > (int)$r['stock_quantity']) {
                    throw new Exception("Sorry, only {$r['stock_quantity']} left of {$r['product_name']}.");
                }

                $price = (float)$r['price']; 
                $subtotal = $price * $q;
                $total += $subtotal;

                $ordered_items[] = [
                    'name' => $r['product_name'],
                    'qty' => $q,
                    'price' => $price,
                    'subtotal' => $subtotal
                ];

                mysqli_stmt_bind_param($ins, 'iiid', $oid, $pid, $q, $price); 
                mysqli_stmt_execute($ins);

                // deduct stock
                mysqli_stmt_bind_param($upd, 'ii', $q, $pid);
                mysqli_stmt_execute($upd);
            }

            $st = mysqli_prepare($conn, "UPDATE orders SET total_amount=? WHERE order_id=?");
            mysqli_stmt_bind_param($st, 'di', $total, $oid); 
            mysqli_stmt_execute($st);

            $points = (int)floor($total / 50);
            $st = mysqli_prepare($conn, "UPDATE customers SET loyalty_points = loyalty_points + ? WHERE customer_id=?");
            mysqli_stmt_bind_param($st, 'ii', $points, $cid); 
            mysqli_stmt_execute($st);

            mysqli_commit($conn);
            
            $new_order_data = [
                'order_id' => $oid,
                'date'     => date('Y-m-d H:i:s'),
                'customer' => $_SESSION['username'] ?? 'Customer',
                'items'    => $ordered_items,
                'total'    => $total,
                'points'   => $points
            ];

            $success = "Order #$oid placed! Please present your Order Slip to the cashier to pay.";
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            $errors[] = $ex->getMessage();
        }
    }
}

// ---- Fetch Data ----
$points = 0;
if ($cid) {
    $st = mysqli_prepare($conn, "SELECT loyalty_points FROM customers WHERE customer_id=?");
    mysqli_stmt_bind_param($st, 'i', $cid); 
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_row($res);
    $points = (int)($row[0] ?? 0);
}

$prods = mysqli_query($conn, "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON c.category_id = p.category_id ORDER BY c.category_name, p.product_name");

$st = mysqli_prepare($conn, "SELECT o.order_id, o.order_date, o.total_amount,
        (SELECT GROUP_CONCAT(CONCAT(oi.quantity,' x ',p.product_name) SEPARATOR ', ')
           FROM order_items oi JOIN products p ON p.product_id=oi.product_id WHERE oi.order_id=o.order_id) items
        FROM orders o WHERE o.customer_id=? ORDER BY o.order_id DESC LIMIT 10");
mysqli_stmt_bind_param($st, 'i', $cid); 
mysqli_stmt_execute($st);
$history = mysqli_stmt_get_result($st);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order – Cup of Joe-rnal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/logo-sm.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="menu.css?v=<?= @filemtime(__DIR__ . '/menu.css') ?>">
    <style>
    .top{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:22px}
    .top .brand{padding:0;font-size:1.3rem;font-weight:700;color:var(--orange)}
    .who{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
    
    .bill{position:sticky;top:20px}
    .line{display:flex;justify-content:space-between;gap:8px;padding:8px 0;font-size:.86rem;border-bottom:1px solid var(--line)}
    .line small{color:var(--muted)}
    .tot{display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;padding:12px 0}
    .bill button.go{width:100%;padding:14px;font-size:1rem;cursor:pointer}
    .empty{color:var(--muted);font-size:.85rem;padding:14px 0;text-align:center}

    /* Modal Styling */
    .modal-bg { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 20px; }
    .slip-card { background: #fff; width: 100%; max-width: 380px; border-radius: 20px; padding: 24px; font-family: 'Courier New', Courier, monospace; color: #333; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .slip-header { text-align: center; border-bottom: 2px dashed #aaa; padding-bottom: 12px; margin-bottom: 12px; }
    .slip-header h3 { margin: 0; font-family: 'Poppins', sans-serif; font-weight: 700; color: #4a2c2a; }
    .slip-row { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px; }
    .slip-items { border-bottom: 2px dashed #aaa; padding-bottom: 12px; margin-bottom: 12px; }
    .slip-total { font-weight: bold; font-size: 1.1rem; display: flex; justify-content: space-between; margin-bottom: 16px; }
    .slip-actions { display: flex; gap: 10px; }
    .slip-actions button, .slip-actions a { flex: 1; text-align: center; font-family: 'Poppins', sans-serif; text-decoration: none; }

    @media print {
        body * { visibility: hidden; }
        .slip-card, .slip-card * { visibility: visible; }
        .slip-card { position: absolute; left: 0; top: 0; width: 100%; max-width: 100%; box-shadow: none; }
        .slip-actions { display: none !important; }
    }
    </style>
</head>
<body>
<div class="app"><main class="content">

<div class="top">
  <div class="brand"><img src="assets/logo-sm.png" alt="Cup of Joe-rnal logo"><span>Cup of Joe-rnal</span></div>
  <div class="who">
    <span>Hi, <b><?= $e($_SESSION['username'] ?? 'there') ?></b></span>
    <span class="pill">⭐ <?= $points ?> loyalty pts</span>
    <a class="btn alt" href="logout.php">Logout</a>
  </div>
</div>

<h2>What would you like today?</h2>
<?php if ($success): ?><div class="alert ok"><?= $e($success) ?></div><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="alert err"><?= $e($er) ?></div><?php endforeach; ?>

<form method="post" class="pos" novalidate>
  <section>
    <?php include 'menu_items.php'; ?>
  </section>

  <aside class="panel bill">
    <h3>Your order</h3>
    <div id="lines"><div class="empty">No items yet</div></div>
    <div class="tot"><span>Total</span><span id="total">₱0.00</span></div>
    <button class="go" type="submit" name="place_order">Place Order</button>
  </aside>
</form>

<div class="panel" style="margin-top:24px">
  <b>My recent orders</b>
  <table>
    <tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Slip</th></tr>
    <?php $n = 0; while ($h = mysqli_fetch_assoc($history)): $n++; ?>
      <tr>
        <td>#<?= (int)$h['order_id'] ?></td>
        <td><?= $e($h['order_date']) ?></td>
        <td><?= $e($h['items']) ?></td>
        <td>₱<?= number_format($h['total_amount'], 2) ?></td>
        <td>
          <a class="btn sm alt" href="order_slip.php?id=<?= (int)$h['order_id'] ?>" target="_blank">📄 Print Slip</a>
        </td>
      </tr>
    <?php endwhile; if (!$n): ?>
      <tr><td colspan="5" class="empty">No orders yet — pick something above!</td></tr>
    <?php endif; ?>
  </table>
</div>

<!-- Order Slip Modal -->
<?php if ($new_order_data): ?>
<div class="modal-bg" id="slipModal">
  <div class="slip-card">
    <div class="slip-header">
      <img class="slip-logo" src="assets/logo-sm.png" alt="">
      <h3>Cup of Joe-rnal</h3>
      <p style="margin:4px 0;font-size:0.75rem;">ORDER SLIP - UNPAID</p>
      <p style="margin:0;font-size:0.75rem;">Show this slip to the cashier to pay</p>
    </div>
    
    <div class="slip-row"><span>Order ID:</span><b>#<?= $new_order_data['order_id'] ?></b></div>
    <div class="slip-row"><span>Date:</span><span><?= $new_order_data['date'] ?></span></div>
    <div class="slip-row"><span>Customer:</span><span><?= $e($new_order_data['customer']) ?></span></div>
    <br>
    
    <div class="slip-items">
      <?php foreach ($new_order_data['items'] as $it): ?>
        <div class="slip-row">
          <span><?= $it['qty'] ?>x <?= $e($it['name']) ?></span>
          <span>₱<?= number_format($it['subtotal'], 2) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="slip-total">
      <span>TOTAL DUE:</span>
      <span>₱<?= number_format($new_order_data['total'], 2) ?></span>
    </div>

    <div class="slip-actions">
      <button class="btn" onclick="window.print()">🖨️ Print</button>
      <button class="btn alt" onclick="document.getElementById('slipModal').remove()">Close</button>
    </div>
  </div>
</div>
<?php endif; ?>

</main></div>

<script>
const peso = n => '₱' + n.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});

function bump(id, d) { 
    const i = document.getElementById('q' + id); 
    i.value = Math.max(0, Math.min(+i.max, (+i.value || 0) + d)); 
    render(); 
}

function render() {
    let html = '', total = 0;
    document.querySelectorAll('.step input').forEach(i => {
        const q = +i.value || 0; 
        if (q < 1) return;
        const sub = q * +i.dataset.price; 
        total += sub;
        
        const nameText = document.createTextNode(i.dataset.name);
        const div = document.createElement('div');
        div.appendChild(nameText);
        
        html += `<div class="line"><div>${div.innerHTML}<br><small>x ${q} · ${peso(+i.dataset.price)}</small></div><b>${peso(sub)}</b></div>`;
    });
    document.getElementById('lines').innerHTML = html || '<div class="empty">No items yet</div>';
    document.getElementById('total').textContent = peso(total);
    if (window.markSelected) markSelected();
}

render();
</script>
</body>
</html>