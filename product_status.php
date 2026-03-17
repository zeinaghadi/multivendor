<?php
// 1. Database Connection
$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Fetch Products
// Added 'price' and 'is_approved' based on your request
$sql = "SELECT product_name,product_quantity, product_price,approved_by_admin FROM products ORDER BY product_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Status | Nova Pixel</title>
    <link rel="stylesheet" href="product_status.css">
    <link rel="stylesheet" href="vendor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="nova-layout">

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
                    <li><a href="product_status.php" class="active">Status</a></li>
                    <li><a href="add_product.php">Add Product</a></li>
                    <li><a href="delete_update_product.php">Delete/Update</a></li>
                </ul>
            </li>
            <li><a href="orders.php"><i class="fa-solid fa-box"></i> Orders</a></li>
            <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
            <li><a href="index.html"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>

    <main class="dashboard-content">
        <header class="dash-header">
            <div class="header-title">
                <h1>Product <span class="highlight">Status</span></h1>
                <p>Real-time inventory tracking for Nova Pixel.</p>
            </div>
            <button class="btn-primary-nova" onclick="window.location.href='add_product.php'">
                <i class="fa-solid fa-plus"></i> Add New Product
            </button>
        </header>

        <section class="status-card">
            <table class="nova-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Admin Approval</th>
                        <th>Stock Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            // Logic for Stock Status Badge
                            $qty = $row['quantity'];
                            if ($qty == 0) {
                                $badgeClass = "st-out";
                                $statusText = "Out of Stock";
                            } elseif ($qty <= 5) {
                                $badgeClass = "st-low";
                                $statusText = "Low Stock";
                            } else {
                                $badgeClass = "st-in";
                                $statusText = "In Stock";
                            }

                            // Logic for Admin Approval Icon
                            $isApproved = $row['is_approved']; // Assuming 1 for yes, 0 for no
                            $approvalHTML = ($isApproved == 1) 
                                ? '<span style="color: #2ecc71;"><i class="fa-solid fa-circle-check"></i> Approved</span>' 
                                : '<span style="color: #f1c40f;"><i class="fa-solid fa-clock"></i> Pending</span>';

                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($row['product_name']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                            echo "<td>$" . number_format($row['price'], 2) . "</td>";
                            echo "<td>" . $qty . "</td>";
                            echo "<td><span class='badge $badgeClass'>$statusText</span></td>";
                            echo "<td>$approvalHTML</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;'>No products found in your inventory.</td></tr>";
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