<?php
session_start();
require_once 'db.php';

// Fixed admin credentials
const ADMIN_EMAIL    = 'admin@cupofjoernal.com';
const ADMIN_PASSWORD = 'Admin@123';

$err   = '';$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {$email = trim($_POST['email'] ?? '');$pw    = $_POST['password'] ?? '';$role  = ($_POST['role'] ?? '') === 'Admin' ? 'Admin' : 'Customer';$ok    = false;

    if ($role === 'Admin') {
        if (hash_equals(ADMIN_EMAIL, strtolower($email)) && hash_equals(ADMIN_PASSWORD, $pw)) {$ok = true;
            $u = [
                'user_id'     => 0,
                'full_name'   => 'Administrator',
                'username'    => 'admin',
                'role'        => 'Admin',
                'customer_id' => null
            ];
        }
    } else {
        // Case-insensitive lookup for email/username and role
        $st = mysqli_prepare($conn, "SELECT user_id, full_name, username, password, role, customer_id
                                     FROM users
                                     WHERE (LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?))
                                       AND LOWER(role) = 'customer'
                                     LIMIT 1");
        mysqli_stmt_bind_param($st, 'ss', $email,$email);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $u   = mysqli_fetch_assoc($res);

        if ($u) {
            if (password_verify($pw, $u['password'])) {$ok = true;
            } elseif (hash_equals($u['password'], $pw)) {$ok   = true;
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $s2   = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
                mysqli_stmt_bind_param($s2, 'si', $hash,$u['user_id']);
                mysqli_stmt_execute($s2);
            }
        }
    }

    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['user_id']     =$u['user_id'];
        $_SESSION['username']    = !empty($u['full_name']) ? $u['full_name'] :$u['username'];
        $_SESSION['role']        =$u['role'];
        $_SESSION['customer_id'] =$u['customer_id'];

        header('Location: ' . ($role === 'Admin' ? 'index.php' : 'customer.php'));
        exit();
    }

    $err = 'Incorrect email, password, or account type.';
}

$e = fn($s) => htmlspecialchars($s, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Log in – Cup of Joe-rnal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="auth.css">
</head>
<body>

<a class="skip" href="#main">Skip to form</a>

<div class="auth">

    <div class="hero">
        <img src="assets/logo.png" alt="Cup of Joe-rnal coffee shop logo">
        <h1>Every cup tells a story</h1>
        <p>Log in and keep brewing.</p>
    </div>

    <main class="card" id="main">
        <h2 id="t">Log in to your account</h2>

        <?php if (isset($_GET['registered'])): ?>
            <div class="msg ok" role="alert" id="m">Account created! Please log in.</div>
        <?php elseif (!empty($err)): ?>
            <div class="msg err" role="alert" id="m"><?= $e($err) ?></div>
        <?php endif; ?>

        <form method="post" id="f" aria-labelledby="t">

            <fieldset class="roles">
                <legend>I am a</legend>
                <label>
                    <input type="radio" name="role" value="Customer" <?= ($_POST['role'] ?? 'Customer') === 'Customer' ? 'checked' : '' ?>>
                    <span>Customer</span>
                </label>
                <label>
                    <input type="radio" name="role" value="Admin" <?= ($_POST['role'] ?? '') === 'Admin' ? 'checked' : '' ?>>
                    <span>Admin</span>
                </label>
            </fieldset>

            <label for="email">Email address</label>
            <input type="email" id="email" name="email" autocomplete="username" required value="<?= $e($email) ?>">

            <label for="password">Password</label>
            <div class="pw">
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                <button type="button" data-toggle="password" aria-pressed="false" aria-label="Show password">Show</button>
            </div>

            <button class="submit" type="submit">Log in</button>
        </form>

        <p class="swap">Don't have an account? <a href="signup.php">Sign up</a></p>
    </main>

</div>

<script>
document.querySelectorAll('[data-toggle]').forEach(function (btn) {
    btn.onclick = function () {
        var input = document.getElementById('password');
        var show  = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
        btn.setAttribute('aria-pressed', show);
        btn.setAttribute('aria-label', (show ? 'Hide ' : 'Show ') + 'password');
    };
});
</script>

</body>
</html>