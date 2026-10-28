<?php 
include 'db.php'; 
include 'header.php'; 

// Get user role from session
$role = $_SESSION['role'] ?? 'customer';

$edit_id = 0;
$edit_name = "";

// ONLY ADMIN CAN EDIT OR DELETE
if ($role === 'Admin') {
    if (isset($_GET['edit'])) {
        $edit_id = (int)$_GET['edit'];
        $res = mysqli_query($conn, "SELECT * FROM categories WHERE category_id = $edit_id");
        if ($row = mysqli_fetch_assoc($res)) {
            $edit_name = $row['category_name'];
        }
    }

    if (isset($_POST['save_category'])) {
        $category_id = (int)$_POST['category_id'];
        $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);

        if (!empty($category_name)) {
            if ($category_id > 0) {
                mysqli_query($conn, "UPDATE categories SET category_name='$category_name' WHERE category_id=$category_id");
            } else {
                mysqli_query($conn, "INSERT INTO categories (category_name) VALUES ('$category_name')");
            }
            header("Location: categories.php");
            exit();
        }
    }

    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        mysqli_query($conn, "DELETE FROM categories WHERE category_id = $id");
        header("Location: categories.php");
        exit();
    }
}
?>

<h2>Categories Management</h2>

<!-- Render Add/Edit Form ONLY if user is Admin -->
<?php if ($role === 'Admin'): ?>
<form method="POST" action="categories.php" style="margin-bottom: 20px;">
    <input type="hidden" name="category_id" value="<?php echo $edit_id; ?>">
    <input type="text" name="category_name" placeholder="Category Name" value="<?php echo htmlspecialchars($edit_name); ?>" required>
    <button type="submit" name="save_category"><?php echo $edit_id > 0 ? "Update Category" : "Add Category"; ?></button>
    <?php if ($edit_id > 0): ?>
        <a href="categories.php" style="margin-left: 10px;">Cancel</a>
    <?php endif; ?>
</form>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Category Name</th>
            <!-- Show Action column header ONLY for Admin -->
            <?php if ($role === 'Admin'): ?>
                <th>Action</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php
        $result = mysqli_query($conn, "SELECT * FROM categories");
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>{$row['category_id']}</td>";
            echo "<td>{$row['category_name']}</td>";
            
            // Hide Action links for Customers
            if ($role === 'Admin') {
                echo "<td>
                        <a href='categories.php?edit={$row['category_id']}'>Edit</a> | 
                        <a href='categories.php?delete={$row['category_id']}' onclick='return confirm(\"Delete this category?\")'>Delete</a>
                      </td>";
            }
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>