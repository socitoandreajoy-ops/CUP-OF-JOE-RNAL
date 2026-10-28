<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (($_SESSION['role'] ?? '') !== 'Admin') { header('Location: ' . (($_SESSION['role'] ?? '') === 'Customer' ? 'customer.php' : 'login.php')); exit; }
$cur = basename($_SERVER['PHP_SELF']);
$menu = ['OPERATIONS'=>['new_order.php'=>'🛒 New Order','orders.php'=>'🧾 Customer Orders'],
 'INSIGHTS'=>['index.php'=>'📊 Dashboard','reports.php'=>'📈 Reports'],
 'CATALOG'=>['categories.php'=>'🗂 Categories','products.php'=>'☕ Products','inventory.php'=>'📦 Inventory'],
 'PEOPLE'=>['customers.php'=>'👥 Customers','employees.php'=>'🧑‍🍳 Employees']];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cup of Joe-rnal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="assets/logo-sm.png">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="menu.css?v=<?= @filemtime(__DIR__ . '/menu.css') ?>"></head><body>
<div class="app">
<aside class="side">
  <div class="brand"><img src="assets/logo-sm.png" alt="Cup of Joe-rnal logo"><span>Cup of<br>Joe-rnal</span></div>
  <?php foreach ($menu as $group => $items): ?>
    <small><?= $group ?></small>
    <?php foreach ($items as $f => $l): ?><a href="<?= $f ?>" class="<?= $cur === $f ? 'active' : '' ?>"><?= $l ?></a><?php endforeach; ?>
  <?php endforeach; ?>
  <div class="user">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'admin') ?> (<?= htmlspecialchars($_SESSION['role'] ?? 'Admin') ?>)<br><a href="logout.php">Logout</a></div>
</aside>
<main class="content">