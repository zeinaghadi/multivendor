<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: test_login.php");
    exit();
}

$v_id = $_SESSION['user_id']; 

$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8");

// update order status
$message = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status_btn'])) {
    $o_id = $_POST['order_id'];
    $new_status = $_POST['new_status']; 

    // عشان يتأكد انه الطلب مش cancelled or delivered
    $check_sql = "SELECT order_status FROM orders WHERE order_id = ? AND vendor_id_fk = ?";
    $c_stmt = $conn->prepare($check_sql);
    $c_stmt->bind_param("ii", $o_id, $v_id);
    $c_stmt->execute();
    $current_res = $c_stmt->get_result()->fetch_assoc();

    if ($current_res) {
        $old_status = $current_res['order_status'];
        
        // بمنع التعديل اذا الطلب cancelled or delivered
        if ($old_status == 'delivered' || $old_status == 'cancelled') {
            $message = "Cannot update: This order is already $old_status.";
            $msg_type = "error";
        } else {
            $update_sql = "UPDATE orders SET order_status = ? WHERE order_id = ? AND vendor_id_fk = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sii", $new_status, $o_id, $v_id); 
            
            if ($stmt->execute()) {
                $message = "Order #$o_id status updated to $new_status!";
                $msg_type = "success";
            }
            $stmt->close();
        }
    }
}


$sql = "SELECT order_id, customer_id_fk, order_date, grand_total, order_status 
        FROM orders 
        WHERE vendor_id_fk = ? 
        ORDER BY order_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $v_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nashmi store | Order Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #d72229;
            --clay: #a76f58;
            --night: #170505;
            --bg-soft: #f8f9fa;
            --white: #ffffff;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-soft);
            color: var(--night);
            display: flex;
            min-height: 100vh;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 280px; background: var(--night); color: white;
            padding: 40px 20px; position: fixed; height: 100vh; z-index: 1000;
        }
        .sidebar-logo { font-size: 1.8rem; font-weight: 800; margin-bottom: 50px; text-align: center; letter-spacing: -1px; }
        .sidebar-logo span { color: var(--primary-red); }
        .sidebar ul { list-style: none; }
        .sidebar ul li a {
            color: rgba(255,255,255,0.6); text-decoration: none; padding: 16px 22px;
            display: flex; align-items: center; gap: 15px; border-radius: 20px;
            transition: var(--transition); font-weight: 600;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: rgba(215, 34, 41, 0.1); color: white; }
        .sidebar ul li a.active { background: var(--primary-red); box-shadow: 0 10px 20px rgba(215, 34, 41, 0.2); }

        /* --- Main Content --- */
        .dashboard-content { margin-left: 280px; width: calc(100% - 280px); padding: 50px; animation: fadeIn 0.8s ease-out; }

        header { margin-bottom: 40px; }
        header h1 { font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; }
        header h1 span { color: var(--primary-red); }

        /* --- Elegant Table Card --- */
        .status-card {
            background: var(--white); border-radius: 35px; padding: 35px;
            box-shadow: 0 20px 50px rgba(23, 5, 5, 0.03);
            border: 1px solid rgba(167, 111, 88, 0.05);
        }

        .nova-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .nova-table th { 
            padding: 15px 20px; color: var(--clay); text-transform: uppercase; 
            font-size: 0.75rem; font-weight: 800; text-align: left; letter-spacing: 1px;
        }
        .nova-table td { 
            background: #fafbfc; padding: 20px; 
            border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;
            transition: var(--transition);
        }
        .nova-table tr:hover td { background: #fff; transform: scale(1.005); }
        .nova-table tr td:first-child { border-left: 1px solid #f1f5f9; border-radius: 20px 0 0 20px; }
        .nova-table tr td:last-child { border-right: 1px solid #f1f5f9; border-radius: 0 20px 20px 0; }

        /* --- Status Badges (Refined) --- */
        .badge {
            padding: 8px 14px; border-radius: 12px; font-size: 0.75rem; font-weight: 800;
            display: inline-flex; align-items: center; gap: 6px; text-transform: uppercase;
        }
        .status-pending { background: #fff9e6; color: #b78a02; }
        .status-processing { background: #eef6ff; color: #2b6cb0; }
        .status-shipped { background: #f3ebff; color: #6b46c1; }
        .status-delivered { background: #e6fffa; color: #2c7a7b; }
        .status-cancelled { background: #fff5f5; color: var(--primary-red); }

        /* --- Action Controls --- */
        .status-select {
            padding: 10px 14px; border-radius: 14px; border: 2px solid #f1f5f9;
            background: white; font-family: inherit; font-size: 0.85rem; font-weight: 600;
            cursor: pointer; outline: none; transition: var(--transition);
        }
        .status-select:focus { border-color: var(--primary-red); box-shadow: 0 0 0 4px rgba(215, 34, 41, 0.05); }

        .btn-update {
            background: var(--night); color: white; border: none; width: 42px; height: 42px;
            border-radius: 14px; cursor: pointer; transition: var(--transition);
            display: flex; align-items: center; justify-content: center;
        }
        .btn-update:hover { 
            background: var(--primary-red); transform: rotate(90deg) scale(1.1); 
            box-shadow: 0 10px 20px rgba(215, 34, 41, 0.2);
        }

        /* --- Notifications --- */
        #status-msg {
            position: fixed; top: 30px; right: 30px; padding: 20px 30px; border-radius: 20px;
            color: white; z-index: 1001; font-weight: 700; box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            display: flex; align-items: center; gap: 12px; animation: slideIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .msg-success { background: #27ae60; border-bottom: 4px solid #1e8449; }
        .msg-error { background: var(--primary-red); border-bottom: 4px solid #a51a1f; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideIn { from { transform: translateX(120%); } to { transform: translateX(0); } }

        @media (max-width: 1100px) {
            .sidebar { width: 100px; padding: 30px 10px; }
            .sidebar-logo span, .sidebar ul li a span { display: none; }
            .dashboard-content { margin-left: 100px; width: calc(100% - 100px); }
        }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="sidebar-logo">Store<span>نشمي</span></div>
    <ul>
        <li><a href="vendor_dashboard.php"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
        <li><a href="delete_update_product.php"><i class="fa-solid fa-tags"></i> <span>Products</span></a></li>
        <li><a href="orders.php" class="active"><i class="fa-solid fa-box"></i> <span>Orders</span></a></li>
        <li><a href="vendor_profile.php"><i class="fa-solid fa-user-gear"></i> <span>Profile</span></a></li>
        <li style="margin-top: 40px;"><a href="log_out.php" style="color: #ffbaba;"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
    </ul>
</nav>

<main class="dashboard-content">
    <header>
        <h1>Marketplace <span>Orders</span></h1>
        <p style="color: var(--clay); font-weight: 500; margin-top: 5px;">Process and monitor your customer transactions.</p>
    </header>

    <?php if (isset($message) && $message != ""): ?>
        <div id="status-msg" class="msg-<?php echo $msg_type; ?>">
            <i class="fa-solid <?php echo $msg_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i> 
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="status-card">
        <table class="nova-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Client Ref</th>
                    <th>Date</th>
                    <th>Transaction</th>
                    <th>Status</th>
                    <th>Operation</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (isset($result) && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $status = $row['order_status'];
                        $is_final = ($status == 'delivered' || $status == 'cancelled');
                        
                        echo "<tr>";
                        echo "<td><span style='font-weight:800; color:var(--night)'>#ORD-{$row['order_id']}</span></td>";
                        echo "<td><span style='color:var(--clay); font-weight:600'>CUST-{$row['customer_id_fk']}</span></td>";
                        echo "<td>" . date("M d, Y", strtotime($row['order_date'])) . "</td>";
                        echo "<td><span style='color:var(--primary-red); font-weight:800;'>JOD " . number_format($row['grand_total'], 2) . "</span></td>";
                        echo "<td><span class='badge status-$status'>$status</span></td>";
                        echo "<td>";
                        
                        if (!$is_final) {
                            echo "<form action='orders.php' method='POST' style='display:flex; gap:10px;'>
                                    <input type='hidden' name='order_id' value='{$row['order_id']}'>
                                    <select name='new_status' class='status-select'>
                                        <option value='pending' ".($status == 'pending'?'selected':'').">Pending</option>
                                        <option value='processing' ".($status == 'processing'?'selected':'').">Processing</option>
                                        <option value='shipped' ".($status == 'shipped'?'selected':'').">Shipped</option>
                                     
                                    </select>
                                    <button type='submit' name='update_status_btn' class='btn-update' title='Update Status'>
                                        <i class='fa-solid fa-arrow-right-rotate'></i>
                                    </button>
                                  </form>";
                        } else {
                            echo "<span style='font-size:0.75rem; color:var(--clay); font-weight:700;'><i class='fa-solid fa-circle-check'></i> COMPLETED</span>";
                        }
                        
                        echo "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; padding:60px; color:var(--clay); font-weight:600;'>No transaction history found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    // Smooth Notification Exit
    const msg = document.getElementById('status-msg');
    if (msg) {
        setTimeout(() => {
            msg.style.transition = "all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55)";
            msg.style.opacity = "0";
            msg.style.transform = "translateX(120%)";
            setTimeout(() => msg.remove(), 600);
        }, 4000);
    }
</script>
</body>
</html>