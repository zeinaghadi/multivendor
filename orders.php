<?php
// 1. الاتصال بقاعدة البيانات
$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. منطق التحديث (الذي يعمل عند الضغط على علامة الصح ✓)
$success_msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status_btn'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        $success_msg = "تم تحديث الطلب #$order_id بنجاح!";
    }
    $stmt->close();
}

// 3. جلب البيانات الحقيقية من الجدول
$sql = "SELECT order_id, customer_id_fk, grand_total, order_date, order_status FROM orders ORDER BY order_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Orders | NovaPixel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="orders.css">
    <link rel="stylesheet" href="vendor_dashboard.css">
</head>
<body>

<nav class="sidebar">
    <div class="sidebar-logo">Nova<span>Pixel</span></div>
    <ul>
        <li><a href="vendor_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
        <li class="has-submenu">
            <a href="#" onclick="toggleSubmenu()">
                <i class="fa-solid fa-tags"></i> Products 
                <i class="fa-solid fa-chevron-down" style="margin-left: auto; font-size: 0.8rem;"></i>
            </a>
            <ul id="product-submenu" style="display: none; list-style: none; padding-left: 30px; background: #001a3d;">
                <li><a href="product_status.php">Status</a></li>
                <li><a href="add_product.php">Add Product</a></li>
                <li><a href="delete_update_product.php">Delete/Update</a></li>
            </ul>
        </li>
        <li><a href="orders.php" class="active"><i class="fa-solid fa-box"></i> Orders</a></li>
        <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
        <li><a href="index.html"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</nav>

<main class="dashboard-content">
    <header class="dash-header">
        <h2>Customer <span style="color: #FF3838;">Orders</span></h2>
        
        <?php if ($success_msg !== ""): ?>
            <div style="background: rgba(46, 204, 113, 0.1); border: 1px solid #2ecc71; color: #2ecc71; padding: 10px; border-radius: 8px; margin-top: 10px;">
                <i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>
    </header>

    <section class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer ID</th>
                    <th>Date</th>
                    <th>Grand Total</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $statusClass = strtolower($row['order_status']);
                        echo "<tr>";
                        echo "<td>#" . $row['order_id'] . "</td>";
                        echo "<td>" . $row['customer_id_fk'] . "</td>";
                        // هنا يتم عرض التاريخ الحقيقي من قاعدة البيانات
                        echo "<td>" . date('Y-m-d', strtotime($row['order_date'])) . "</td>";
                        echo "<td>$" . number_format($row['grand_total'], 2) . "</td>";
                        echo "<td><span class='status-badge $statusClass'>" . ucfirst($row['order_status']) . "</span></td>";
                        echo "<td>
                                <form action='orders.php' method='POST' class='status-form'>
                                    <input type='hidden' name='order_id' value='" . $row['order_id'] . "'>
                                    <select name='new_status' class='status-select'>
                                        <option value='pending' " . ($row['order_status'] == 'pending' ? 'selected' : '') . ">Pending</option>
                                        <option value='shipping' " . ($row['order_status'] == 'shipping' ? 'selected' : '') . ">Shipping</option>
                                        <option value='delivered' " . ($row['order_status'] == 'delivered' ? 'selected' : '') . ">Delivered</option>
                                    </select>
                                    <button type='submit' name='update_status_btn' class='btn-update'>
                                        <i class='fa-solid fa-check'></i>
                                    </button>
                                </form>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>No orders found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </section>
</main>

<script>
    function toggleSubmenu() {
        const submenu = document.getElementById('product-submenu');
        submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';
    }
</script>
</body>
</html>