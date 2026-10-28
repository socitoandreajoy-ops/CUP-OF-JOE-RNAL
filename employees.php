<?php
session_start();
include 'db.php';
if (($_SESSION['role'] ?? '') !== 'Admin') { header('Location: login.php'); exit; }
mysqli_report(MYSQLI_REPORT_OFF);

$e      = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$roles  = ['Barista', 'Cashier', 'Manager'];
$errors = [];
$msg    = $_GET['msg'] ?? '';
$editId = (int)($_GET['edit'] ?? 0);
$form   = ['employee_name' => '', 'role' => '', 'username' => ''];

// Load employee into the form when clicking Edit
if ($editId > 0) {
    $q = mysqli_prepare($conn, "SELECT employee_name, role, username FROM employees WHERE employee_id = ?");
    mysqli_stmt_bind_param($q, 'i', $editId);
    mysqli_stmt_execute($q);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    if ($row) {
        $form = ['employee_name' => $row['employee_name'], 'role' => $row['role'], 'username' => (string)$row['username']];
    } else {
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    // ---------- ADD / UPDATE ----------
    if ($act === 'save') {
        $id     = (int)($_POST['employee_id'] ?? 0);
        $editId = $id;
        $form   = [
            'employee_name' => trim($_POST['employee_name'] ?? ''),
            'role'          => $_POST['role'] ?? '',
            'username'      => trim($_POST['username'] ?? ''),
        ];
        $pw = $_POST['password'] ?? '';

        if ($form['employee_name'] === '')            $errors[] = 'Employee name is required.';
        elseif (strlen($form['employee_name']) > 100) $errors[] = 'Employee name is too long (max 100).';
        if (!in_array($form['role'], $roles, true))   $errors[] = 'Please select a role.';

        $isCashier = $form['role'] === 'Cashier';

        // does this employee already have a password?
        $hasPw = false;
        if ($id > 0) {
            $q = mysqli_prepare($conn, "SELECT password FROM employees WHERE employee_id = ?");
            mysqli_stmt_bind_param($q, 'i', $id);
            mysqli_stmt_execute($q);
            $cur   = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
            $hasPw = !empty($cur['password'] ?? '');
        }

        if ($isCashier || $form['username'] !== '') {
            if (!preg_match('/^[A-Za-z0-9._@-]{4,50}$/', $form['username'])) {
                $errors[] = 'Username must be 4-50 characters (letters, numbers, . _ @ - only).';
            } else {
                $q = mysqli_prepare($conn, "SELECT 1 FROM employees WHERE LOWER(username) = LOWER(?) AND employee_id <> ?");
                mysqli_stmt_bind_param($q, 'si', $form['username'], $id);
                mysqli_stmt_execute($q);
                if (mysqli_fetch_row(mysqli_stmt_get_result($q))) $errors[] = 'That username is already taken.';
            }
        }
        if ($pw !== '' && strlen($pw) < 6)  $errors[] = 'Password must be at least 6 characters.';
        if ($isCashier && $pw === '' && !$hasPw) $errors[] = 'Set a password so this cashier can log in.';

        if (!$errors) {
            $uname = $form['username'] !== '' ? $form['username'] : null;

            if ($id > 0) {
                if ($pw !== '') {
                    $hash = password_hash($pw, PASSWORD_DEFAULT);
                    $st = mysqli_prepare($conn, "UPDATE employees SET employee_name=?, role=?, username=?, password=? WHERE employee_id=?");
                    mysqli_stmt_bind_param($st, 'ssssi', $form['employee_name'], $form['role'], $uname, $hash, $id);
                } else {
                    $st = mysqli_prepare($conn, "UPDATE employees SET employee_name=?, role=?, username=? WHERE employee_id=?");
                    mysqli_stmt_bind_param($st, 'sssi', $form['employee_name'], $form['role'], $uname, $id);
                }
                $done = 'updated';
            } else {
                $hash = $pw !== '' ? password_hash($pw, PASSWORD_DEFAULT) : null;
                $st = mysqli_prepare($conn, "INSERT INTO employees (employee_name, role, username, password) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($st, 'ssss', $form['employee_name'], $form['role'], $uname, $hash);
                $done = 'added';
            }

            if (mysqli_stmt_execute($st)) {
                header('Location: employees.php?msg=' . $done);
                exit;
            }
            $errors[] = 'Could not save: ' . mysqli_stmt_error($st);
        }
    }

    // ---------- DELETE ----------
    if ($act === 'delete') {
        $id = (int)($_POST['employee_id'] ?? 0);
        $st = mysqli_prepare($conn, "DELETE FROM employees WHERE employee_id = ?");
        mysqli_stmt_bind_param($st, 'i', $id);
        if (mysqli_stmt_execute($st)) {
            header('Location: employees.php?msg=deleted');
            exit;
        }
        $errors[] = "Can't delete this employee because they already have orders on record. Edit them instead (change role or username).";
    }
}

$list = mysqli_query($conn, "SELECT employee_id, employee_name, role, username,
                                    (password IS NOT NULL AND password <> '') AS has_pw
                             FROM employees ORDER BY employee_id");

include 'header.php';
$msgs = ['added' => 'Employee added.', 'updated' => 'Employee updated.', 'deleted' => 'Employee deleted.'];
?>
<h2>Employees Management</h2>

<?php if (isset($msgs[$msg])): ?><div class="alert ok"><?= $msgs[$msg] ?></div><?php endif; ?>
<?php foreach ($errors as $er): ?><div class="alert err"><?= $e($er) ?></div><?php endforeach; ?>

<div class="panel"><b><?= $editId > 0 ? 'Edit employee' : 'Add new employee' ?></b>
<form class="f" method="post" action="employees.php<?= $editId > 0 ? '?edit=' . $editId : '' ?>" autocomplete="off" novalidate>
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="employee_id" value="<?= $editId ?>">

  <div><label>Full name</label>
    <input type="text" name="employee_name" value="<?= $e($form['employee_name']) ?>" placeholder="e.g. Cardo Dalisay"></div>

  <div><label>Role</label>
    <select name="role">
      <option value="">-- Select role --</option>
      <?php foreach ($roles as $r): ?>
        <option value="<?= $r ?>" <?= $form['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
      <?php endforeach; ?>
    </select></div>

  <div><label>Login username <small>(cashiers)</small></label>
    <input type="text" name="username" value="<?= $e($form['username']) ?>" placeholder="e.g. cardo" autocomplete="off"></div>

  <div><label>Password <small>(cashiers)</small></label>
    <input type="password" name="password" autocomplete="new-password"
           placeholder="<?= $editId > 0 ? 'Leave blank to keep current' : 'At least 6 characters' ?>"></div>

  <div class="sub" style="grid-column:1/-1">Only employees with the <b>Cashier</b> role can log in. They sign in on the login page by choosing "Cashier".</div>

  <div style="align-self:end">
    <button type="submit"><?= $editId > 0 ? 'Save changes' : 'Add employee' ?></button>
    <?php if ($editId > 0): ?> <a class="btn alt" href="employees.php">Cancel</a><?php endif; ?>
  </div>
</form>
</div>

<div class="panel">
  <table>
    <thead><tr><th>ID</th><th>Employee Name</th><th>Role</th><th>Username</th><th>Login</th><th>Action</th></tr></thead>
    <tbody>
    <?php while ($r = mysqli_fetch_assoc($list)):
        $canLogin = $r['role'] === 'Cashier' && !empty($r['username']) && (int)$r['has_pw'] === 1; ?>
      <tr>
        <td><?= (int)$r['employee_id'] ?></td>
        <td><?= $e($r['employee_name']) ?></td>
        <td><span class="pill"><?= $e($r['role']) ?></span></td>
        <td><?= $e($r['username'] ?? '') ?></td>
        <td><?= $canLogin ? '<span class="pill">✅ Can log in</span>' : '<span class="sub">-</span>' ?></td>
        <td style="white-space:nowrap">
          <a class="btn sm alt" href="employees.php?edit=<?= (int)$r['employee_id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this employee?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="employee_id" value="<?= (int)$r['employee_id'] ?>">
            <button class="btn sm del" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

</main></div></body></html>