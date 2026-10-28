<?php 
include 'db.php'; 
include 'header.php'; 

// Handle Quick Restock Form
if (isset($_POST['restock_product'])) {
    $product_id = (int)$_POST['product_id'];
    $add_quantity = (int)$_POST['add_quantity'];

    if ($product_id > 0 && $add_quantity > 0) {
        mysqli_query($conn, "UPDATE products SET stock_quantity = stock_quantity + $add_quantity WHERE product_id = $product_id");
        header("Location: inventory.php");
        exit();
    }
}
?>

<h2>Inventory Tracking & Restock</h2>

<h3 style="color: #d9534f;">⚠️ Low Stock Items (Below 10 units)</h3>
<table>
    <thead>
        <tr style="background-color: #f2dede;">
            <th>Product Name</th>
            <th>Category</th>
            <th>Current Stock</th>
            <th>Price</th>
            <th>Quick Restock</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $low_stock_sql = "SELECT p.*, c.category_name 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.category_id 
                          WHERE p.stock_quantity < 10 
                          ORDER BY p.stock_quantity ASC";
        $low_result = mysqli_query($conn, $low_stock_sql);

        if (mysqli_num_rows($low_result) > 0) {
            while ($row = mysqli_fetch_assoc($low_result)) {
                echo "<tr style='background-color: #ffdddd;'>";
                echo "<td><b>{$row['product_name']}</b></td>";
                echo "<td>{$row['category_name']}</td>";
                echo "<td style='color: red; font-weight: bold;'>{$row['stock_quantity']}</td>";
                echo "<td>₱" . number_format($row['price'], 2) . "</td>";
                echo "<td>
                        <form method='POST' action='inventory.php' style='margin:0; display:flex; gap: 5px;'>
                            <input type='hidden' name='product_id' value='{$row['product_id']}'>
                            <input type='number' name='add_quantity' min='1' placeholder='+Qty' required style='width: 70px;'>
                            <button type='submit' name='restock_product' style='background: #28a745; color: white; border: none; padding: 4px 8px; cursor: pointer; border-radius: 3px;'>Add Stock</button>
                        </form>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>All items have sufficient stock (10+ units).</td></tr>";
        }
        ?>
    </tbody>
</table>

<br>

<h3>📦 Complete Stock Overview</h3>
<table>
    <thead>
        <tr>
            <th>Product Name</th>
            <th>Category</th>
            <th>Current Stock</th>
            <th>Status</th>
            <th>Quick Restock</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $all_stock_sql = "SELECT p.*, c.category_name 
                          FROM products p 
                          JOIN categories c ON p.category_id = c.category_id 
                          ORDER BY p.stock_quantity ASC";
        $all_result = mysqli_query($conn, $all_stock_sql);

        while ($row = mysqli_fetch_assoc($all_result)) {
            $status = $row['stock_quantity'] < 10 
                ? "<span style='color: red; font-weight: bold;'>Low Stock</span>" 
                : "<span style='color: green;'>In Stock</span>";

            echo "<tr>";
            echo "<td>{$row['product_name']}</td>";
            echo "<td>{$row['category_name']}</td>";
            echo "<td>{$row['stock_quantity']}</td>";
            echo "<td>{$status}</td>";
            echo "<td>
                    <form method='POST' action='inventory.php' style='margin:0; display:flex; gap: 5px;'>
                        <input type='hidden' name='product_id' value='{$row['product_id']}'>
                        <input type='number' name='add_quantity' min='1' placeholder='+Qty' required style='width: 70px;'>
                        <button type='submit' name='restock_product' style='background: #007bff; color: white; border: none; padding: 4px 8px; cursor: pointer; border-radius: 3px;'>Add Stock</button>
                    </form>
                  </td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>