<?php
session_start();
if (!in_array($_SESSION['role'] ?? '', ['Admin', 'Cashier', 'Manager'])) {
    header('Location: login.php');
    exit();
}

include 'db.php';
include 'header.php';

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$errors = [];
$success = '';
$success_oid = 0;
$mode = $_POST['cust_mode'] ?? 'existing';

// Handle New Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $mode    = ($_POST['cust_mode'] ?? 'existing') === 'new' ? 'new' : 'existing';
    $cust_id = (int)($_POST['customer_id'] ?? 0);
    $emp_id  = (int)($_SESSION['user_id'] ?? 0);
    $qtys    = array_filter(array_map('intval', $_POST['qty'] ?? []), fn($q) => $q > 0);
    $nc_name  = trim($_POST['new_name'] ?? '');
    $nc_phone = trim($_POST['new_phone'] ?? '');
    $nc_email = trim($_POST['new_email'] ?? '');

    if ($mode === 'new') {
        if ($nc_name === '')            $errors[] = 'Enter the new customer\'s name.';
        elseif (strlen($nc_name) > 100) $errors[] = 'Customer name is too long (max 100).';
        if ($nc_phone !== '' && !preg_match('/^(09\d{9}|\+639\d{9})$/', $nc_phone))
            $errors[] = 'Phone must look like 09123456789 or +639123456789.';
        if ($nc_email !== '' && !filter_var($nc_email, FILTER_VALIDATE_EMAIL))
            $errors[] = 'Email format is invalid.';
        if ($nc_email !== '' && !$errors) {
            $q = mysqli_prepare($conn, "SELECT 1 FROM customers WHERE email=?");
            mysqli_stmt_bind_param($q, 's', $nc_email); mysqli_stmt_execute($q);
            if (mysqli_fetch_row(mysqli_stmt_get_result($q))) $errors[] = 'That email is already used by another customer.';
        }
    } elseif ($cust_id < 1) {
        $errors[] = 'Please select a customer (or choose "New customer").';
    }
    if (!$qtys) $errors[] = 'Select at least one product.';

    if (!$errors) {
        mysqli_begin_transaction($conn);
        try {
            if ($mode === 'new') {
                $em = $nc_email !== '' ? $nc_email : null;
                $ph = $nc_phone !== '' ? $nc_phone : null;
                $ci = mysqli_prepare($conn, "INSERT INTO customers (customer_name, email, phone, loyalty_points) VALUES (?, ?, ?, 0)");
                mysqli_stmt_bind_param($ci, 'sss', $nc_name, $em, $ph);
                mysqli_stmt_execute($ci);
                $cust_id = mysqli_insert_id($conn);
            }
            // session user_id comes from the users table, not employees,
            // so only keep it if a matching employee row exists (else NULL)
            $chk = mysqli_prepare($conn, "SELECT employee_id FROM employees WHERE employee_id = ?");
            mysqli_stmt_bind_param($chk, 'i', $emp_id);
            mysqli_stmt_execute($chk);
            $emp_ref = mysqli_fetch_row(mysqli_stmt_get_result($chk))[0] ?? null;

            $st = mysqli_prepare($conn, "INSERT INTO orders (customer_id, employee_id, total_amount) VALUES (?, ?, 0)");
            mysqli_stmt_bind_param($st, 'ii', $cust_id, $emp_ref);
            mysqli_stmt_execute($st);
            $oid = mysqli_insert_id($conn);
            $total = 0;

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
                    throw new Exception("Only {$r['stock_quantity']} left of {$r['product_name']}.");
                }

                $price = (float)$r['price'];
                $total += $price * $q;

                mysqli_stmt_bind_param($ins, 'iiid', $oid, $pid, $q, $price);
                mysqli_stmt_execute($ins);

                mysqli_stmt_bind_param($upd, 'ii', $q, $pid);
                mysqli_stmt_execute($upd);
            }

            $st = mysqli_prepare($conn, "UPDATE orders SET total_amount=? WHERE order_id=?");
            mysqli_stmt_bind_param($st, 'di', $total, $oid);
            mysqli_stmt_execute($st);

            mysqli_commit($conn);
            $success = "Order #$oid created successfully! Total: ₱" . number_format($total, 2);
            $success_oid = $oid;
            if ($mode === 'new') $success .= " (new customer \"$nc_name\" added)";
            $_POST = [];  // clear the form after a successful order
            $mode = 'existing';
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            $errors[] = $ex->getMessage();
        }
    }
}

// Fetch Customers and Products
$customers = mysqli_query($conn, "SELECT customer_id, customer_name FROM customers ORDER BY customer_name");
$prods = mysqli_query($conn, "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON c.category_id = p.category_id ORDER BY c.category_name, p.product_name");
?>

<h2>Place New Order</h2>

<?php if ($success): ?><div class="alert ok"><?= $e($success) ?> <?php if ($success_oid): ?>&nbsp;<a class="btn sm alt" href="order_slip.php?id=<?= $success_oid ?>" target="_blank">🖨️ Print Slip</a><?php endif; ?></div><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="alert err"><?= $e($er) ?></div><?php endforeach; ?>

<form method="post" class="pos" novalidate>
  <section>
    <?php include 'menu_items.php'; ?>
  </section>

  <aside class="panel bill">
    <h3>Customer & Order</h3>
    
    <div class="cust-switch">
      <button type="button" id="tabExisting" class="active" onclick="custMode('existing')">Existing customer</button>
      <button type="button" id="tabNew" onclick="custMode('new')">＋ New customer</button>
    </div>
    <input type="hidden" name="cust_mode" id="cust_mode" value="<?= $e($mode === 'new' ? 'new' : 'existing') ?>">

    <div id="boxExisting">
      <label class="lbl">Customer</label>
      <select name="customer_id">
        <option value="">-- Select Customer --</option>
        <?php while ($c = mysqli_fetch_assoc($customers)): ?>
          <option value="<?= (int)$c['customer_id'] ?>" <?= ((int)($_POST['customer_id'] ?? 0) === (int)$c['customer_id']) ? 'selected' : '' ?>><?= $e($c['customer_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div id="boxNew" style="display:none">
      <label class="lbl">Full name *</label>
      <input type="text" name="new_name" value="<?= $e($_POST['new_name'] ?? '') ?>" placeholder="e.g. Juan Dela Cruz">
      <label class="lbl">Phone <small>(optional)</small></label>
      <input type="text" name="new_phone" value="<?= $e($_POST['new_phone'] ?? '') ?>" placeholder="09123456789">
      <label class="lbl">Email <small>(optional)</small></label>
      <input type="text" name="new_email" value="<?= $e($_POST['new_email'] ?? '') ?>" placeholder="name@email.com">
      <div class="sub" style="margin-top:8px">They'll be saved in Customers when you place the order.</div>
    </div>

    <h4 style="margin:16px 0 8px;">Order Summary</h4>
    <div id="lines"><div class="empty">No items selected</div></div>
    
    <div class="tot" style="display:flex;justify-content:space-between;font-weight:bold;font-size:1.15rem;margin:16px 0;">
      <span>Total</span><span id="total">₱0.00</span>
    </div>
    
    <button class="btn" style="width:100%;" type="submit" name="place_order">Place Order</button>
  </aside>
</form>

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
        
        html += `<div class="row" style="padding:6px 0;font-size:0.86rem;">
                    <div>${div.innerHTML}<br><small>x ${q} · ${peso(+i.dataset.price)}</small></div>
                    <b>${peso(sub)}</b>
                 </div>`;
    });
    document.getElementById('lines').innerHTML = html || '<div class="empty">No items selected</div>';
    document.getElementById('total').textContent = peso(total);
    if (window.markSelected) markSelected();
}

function custMode(m) {
    document.getElementById('cust_mode').value = m;
    document.getElementById('boxExisting').style.display = m === 'new' ? 'none' : '';
    document.getElementById('boxNew').style.display      = m === 'new' ? '' : 'none';
    document.getElementById('tabExisting').classList.toggle('active', m !== 'new');
    document.getElementById('tabNew').classList.toggle('active', m === 'new');
}
custMode(document.getElementById('cust_mode').value);

render();
</script>
</body>
</html>