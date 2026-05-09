<?php
session_start();

// 1. حماية الصفحة وسحب الـ ID
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: login.php"); 
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

// 2. جلب المنتجات باستخدام Prepared Statements
$sql = "SELECT product_id, product_name, product_quantity, product_price, approved_by_admin 
        FROM products 
        WHERE vendor_id_fk = ? 
        ORDER BY product_id DESC";

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
    <title>Nashmi store | Product Inventory Status</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #d72229;
            --deep-maroon: #770e13;
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

        /* --- Sidebar (Matched with Dashboard) --- */
        .sidebar {
            width: 280px;
            background: var(--night);
            color: white;
            padding: 40px 20px;
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }
        .sidebar-logo {
            font-size: 1.8rem; font-weight: 800; margin-bottom: 50px; text-align: center; letter-spacing: -1px;
        }
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
        .main-content {
            margin-left: 280px; padding: 50px; width: calc(100% - 280px);
            animation: fadeIn 0.8s ease-out;
        }

        header { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 45px;
        }
        header h1 { font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; }
        header h1 span { color: var(--primary-red); }

        .btn-add-shortcut { 
            background: var(--night); color: white; text-decoration: none; 
            padding: 14px 28px; border-radius: 18px; font-weight: 700; 
            transition: var(--transition); display: flex; align-items: center; gap: 10px;
            box-shadow: 0 10px 20px rgba(23, 5, 5, 0.1);
        }
        .btn-add-shortcut:hover { 
            background: var(--primary-red); transform: translateY(-3px); 
            box-shadow: 0 15px 30px rgba(215, 34, 41, 0.25);
        }

        /* --- Custom Table Styling --- */
        .table-card { 
            background: var(--white); padding: 35px; border-radius: 40px; 
            box-shadow: 0 20px 50px rgba(23, 5, 5, 0.04);
            border: 1px solid rgba(167, 111, 88, 0.05);
        }

        .nova-table { width: 100%; border-collapse: separate; border-spacing: 0 15px; }
        .nova-table th { 
            text-align: left; padding: 0 20px; color: var(--clay); 
            font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;
        }
        .nova-table td { 
            padding: 22px 20px; background: #fff; 
            border-top: 1px solid #f8f9fa; border-bottom: 1px solid #f8f9fa;
            transition: var(--transition);
        }
        
        .nova-table tr td:first-child { border-left: 1px solid #f8f9fa; border-radius: 25px 0 0 25px; }
        .nova-table tr td:last-child { border-right: 1px solid #f8f9fa; border-radius: 0 25px 25px 0; }
        
        .nova-table tr:hover td { 
            background: #fdfdfd; border-color: var(--clay); 
            transform: scale(1.01);
        }

        /* --- Badges & Status --- */
        .badge { 
            padding: 8px 16px; border-radius: 14px; font-size: 0.8rem; font-weight: 700; 
            display: inline-flex; align-items: center; gap: 8px;
        }
        .st-in { background: #f0fff4; color: #2d8a4e; }
        .st-low { background: #fff9db; color: #9c7b16; }
        .st-out { background: #fff5f5; color: var(--primary-red); }

        .status-pill { 
            padding: 10px 18px; border-radius: 15px; font-weight: 800; font-size: 0.85rem; 
            display: inline-flex; align-items: center; gap: 8px;
        }
        .status-approved { background: rgba(45, 138, 78, 0.1); color: #2d8a4e; }
        .status-pending { background: rgba(167, 111, 88, 0.1); color: var(--clay); }
        .status-rejected { background: rgba(215, 34, 41, 0.1); color: var(--primary-red); }

        .price-text { font-weight: 800; color: var(--night); font-size: 1.1rem; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-logo">Store<span>نشمي</span></div>
        <ul>
            <li><a href="vendor_dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
            <li>
                <a href="#" class="active"><i class="fa-solid fa-tags"></i> Products</a>
                <ul style="list-style: none; padding-left: 25px; margin-top: 10px;">
                    <li><a href="product_status.php" style="color:white; font-size: 0.9rem;">• Status</a></li>
                    <li><a href="add_product.php" style="font-size: 0.9rem; opacity: 0.6;">• Add New</a></li>
                    <li><a href="delete_update_product.php" style="font-size: 0.9rem; opacity: 0.6;">• Edit List</a></li>
                </ul>
            </li>
            <li><a href="orders.php"><i class="fa-solid fa-box"></i> Orders</a></li>
            <li><a href="vendor_profile.php"><i class="fa-solid fa-user-gear"></i> Profile</a></li>
            <li style="margin-top: 40px;"><a href="log_out.php" style="color: #ffbaba;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <header>
            <div>
                <h1>Inventory <span>Status</span></h1>
                <p style="margin-top: 8px; color: var(--clay); font-weight: 500;">Monitor your product lifecycle and stock health.</p>
            </div>
            <a href="add_product.php" class="btn-add-shortcut"><i class="fa-solid fa-plus"></i> Add New Product</a>
        </header>

        <section class="table-card">
            <table class="nova-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Product Details</th>
                        <th>Unit Price</th>
                        <th>Stock Health</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($result) && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $status = $row['approved_by_admin'];
                            $qty = $row['product_quantity'];
                            $statusClass = "status-$status";
                            $icon = ($status == 'approved') ? 'fa-circle-check' : (($status == 'rejected') ? 'fa-circle-xmark' : 'fa-clock');
                            
                            $stockClass = ($qty <= 0) ? 'st-out' : (($qty <= 5) ? 'st-low' : 'st-in');
                            $stockLabel = ($qty <= 0) ? 'Out of Stock' : (($qty <= 5) ? "Low Stock ($qty)" : "In Stock ($qty)");
                        ?>
                            <tr>
                                <td style="color: var(--clay); font-weight: 700;">#<?= $row['product_id'] ?></td>
                                <td>
                                    <div style="font-weight: 800; color: var(--night); font-size: 1rem;"><?= htmlspecialchars($row['product_name']) ?></div>
                                    <small style="color: var(--clay); font-weight: 500;">General Merchandise</small>
                                </td>
                                <td class="price-text">JOD <?= number_format($row['product_price'], 2) ?></td>
                                <td><span class="badge <?= $stockClass ?>"><i class="fa-solid fa-layer-group"></i> <?= $stockLabel ?></span></td>
                                <td>
                                    <span class="status-pill <?= $statusClass ?>">
                                        <i class="fa-solid <?= $icon ?>"></i> <?= ucfirst($status) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:80px;">
                                <div style="font-size: 3rem; color: #eee; margin-bottom: 20px;"><i class="fa-solid fa-box-open"></i></div>
                                <span style="color: var(--clay); font-weight: 600;">Your inventory is currently empty.</span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

</body>
</html>