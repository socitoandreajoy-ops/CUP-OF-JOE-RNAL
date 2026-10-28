<?php 
include 'db.php'; 
include 'header.php'; 

// Block non-admin users
if ($_SESSION['role'] !== 'Admin') {
    echo "<h3 style='color:red;'>Access Denied. Admin privileges required.</h3>";
    exit();
}

$edit_id = 0;
$edit_name = "";
$edit_role = "";

// Check if editing a record
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM employees WHERE employee_id = $edit_id");
    if ($row = mysqli_fetch_assoc($res)) {
        $edit_name = $row['employee_name'];
        $edit_role = $row['role'];
    }
}

// Handle Add or Update Submission
if (isset($_POST['save_employee'])) {
    $employee_id = (int)$_POST['employee_id'];
    $employee_name = mysqli_real_escape_string($conn, $_POST['employee_name']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    if (!empty($employee_name) && !empty($role)) {
        if ($employee_id > 0) {
            // UPDATE existing
            mysqli_query($conn, "UPDATE employees SET employee_name='$employee_name', role='$role' WHERE employee_id=$employee_id");
        } else {
            // INSERT new
            mysqli_query($conn, "INSERT INTO employees (employee_name, role) VALUES ('$employee_name', '$role')");
        }
        header("Location: employees.php");
        exit();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM employees WHERE employee_id = $id");
    header("Location: employees.php");
    exit();
}
?>

<h2>Employees Management</h2>

<!-- Form (Handles both Add and Edit) -->
<form method="POST" action="employees.php" style="margin-bottom: 20px;">
    <input type="hidden" name="employee_id" value="<?php echo $edit_id; ?>">
    <input type="text" name="employee_name" placeholder="Employee Name" value="<?php echo htmlspecialchars($edit_name); ?>" required>
    <select name="role" required>
        <option value="">Select Role</option>
        <option value="Barista" <?php if ($edit_role === 'Barista') echo 'selected'; ?>>Barista</option>
        <option value="Cashier" <?php if ($edit_role === 'Cashier') echo 'selected'; ?>>Cashier</option>
        <option value="Manager" <?php if ($edit_role === 'Manager') echo 'selected'; ?>>Manager</option>
    </select>
    <button type="submit" name="save_employee"><?php echo $edit_id > 0 ? "Update Employee" : "Add Employee"; ?></button>
    <?php if ($edit_id > 0): ?>
        <a href="employees.php" style="margin-left: 10px;">Cancel</a>
    <?php endif; ?>
</form>

<!-- Employees List Table -->
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Employee Name</th>
            <th>Role</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $result = mysqli_query($conn, "SELECT * FROM employees");
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>{$row['employee_id']}</td>";
            echo "<td>{$row['employee_name']}</td>";
            echo "<td>{$row['role']}</td>";
            echo "<td>
                    <a href='employees.php?edit={$row['employee_id']}'>Edit</a> | 
                    <a href='employees.php?delete={$row['employee_id']}' onclick='return confirm(\"Delete this employee?\")'>Delete</a>
                  </td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>