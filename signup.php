<?php
session_start();
include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$err   = '';
$name  = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $pw      = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $role    = 'Customer';

    // Validate the form
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Enter your name and a valid email.';
    } elseif (strlen($pw) < 8) {
        $err = 'Password must be at least 8 characters.';
    } elseif ($pw !== $confirm) {
        $err = 'Passwords do not match.';
    } else {
        // Check if the email is already used
        $st = mysqli_prepare($conn, "SELECT 1 FROM users WHERE email = ?");
        mysqli_stmt_bind_param($st, 's', $email);
        mysqli_stmt_execute($st);

        if (mysqli_fetch_row(mysqli_stmt_get_result($st))) {
            $err = 'That email is already registered.';
        } else {
            // Save both records together, or neither
            mysqli_begin_transaction($conn);

            // 1. Create the customer record
            $s = mysqli_prepare($conn, "INSERT INTO customers (customer_name, phone, email) VALUES (?, ?, ?)");
            $ph = $phone !== '' ? $phone : null;
            mysqli_stmt_bind_param($s, 'sss', $name, $ph, $email);
            mysqli_stmt_execute($s);
            $customer_id = mysqli_insert_id($conn);

            // 2. Create the login account linked to that customer
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $s = mysqli_prepare($conn, "INSERT INTO users (username, full_name, email, password, role, customer_id)
                                        VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($s, 'sssssi', $email, $name, $email, $hash, $role, $customer_id);
            mysqli_stmt_execute($s);

            mysqli_commit($conn);

            header('Location: login.php?registered=1');
            exit();
        }
    }
}

// Short helper to safely print text
$e = fn($s) => htmlspecialchars($s, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Sign up – Cup of Joe-rnal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="auth.css">
</head>
<body>

<a class="skip" href="#main">Skip to form</a>

<div class="auth">

    <div class="hero">
        <img src="assets/logo.png" alt="Cup of Joe-rnal coffee shop logo">
        <h1>Join the Cup of Joe-rnal family</h1>
        <p>Sign up and start earning loyalty points.</p>
    </div>

    <main class="card" id="main">
        <h2 id="t">Create your account</h2>

        <div class="msg err" role="alert" id="m"><?= $e($err) ?></div>

        <form method="post" id="f" aria-labelledby="t">

            <label for="name">Full name</label>
            <input type="text" id="name" name="name" autocomplete="name" required value="<?= $e($name) ?>">

            <label for="email">Email address</label>
            <input type="email" id="email" name="email" autocomplete="email" required value="<?= $e($email) ?>">

            <label for="phone">Contact number</label>
            <input type="tel" id="phone" name="phone" autocomplete="tel" value="<?= $e($phone) ?>">

            <label for="password">Password</label>
            <div class="pw">
                <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8" aria-describedby="h">
                <button type="button" data-toggle="password" aria-pressed="false" aria-label="Show password">Show</button>
            </div>
            <p class="hint" id="h">At least 8 characters.</p>

            <label for="confirm">Confirm password</label>
            <div class="pw">
                <input type="password" id="confirm" name="confirm" autocomplete="new-password" required>
                <button type="button" data-toggle="confirm" aria-pressed="false" aria-label="Show confirm password">Show</button>
            </div>

            <button class="submit" type="submit">Sign up</button>
        </form>

        <p class="swap">Already have an account? <a href="login.php">Log in</a></p>
    </main>

</div>

<script>
// Show / hide password (works for both password boxes)
document.querySelectorAll('[data-toggle]').forEach(function (btn) {
    btn.onclick = function () {
        var input = document.getElementById(btn.dataset.toggle);
        var show  = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
        btn.setAttribute('aria-pressed', show);
        btn.setAttribute('aria-label', (show ? 'Hide ' : 'Show ') + (input.id === 'confirm' ? 'confirm password' : 'password'));
    };
});
</script>

</body>
</html>