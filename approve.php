<?php
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: test_login.php");
    exit();
}
$v_id = $_SESSION['user_id'];

// 2. الاتصال بالقاعدة
$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8mb4");

$message = "";
$msg_type = ""; 

// 3. معالجة قبول أو رفض المنتج
if (isset($_GET['action']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'accept') {
        $status = 'approved';
        $message = " Product has been approved! ✅";
        $msg_type = "success";
    } elseif ($action === 'reject') {
        $status = 'rejected';
        $message = "Product has been rejected ❌";
        $msg_type = "error";
    }

    if (isset($status)) {
        $stmt = $conn->prepare("UPDATE products SET approved_by_admin = ? WHERE product_id = ?");
        $stmt->bind_param("si", $status, $product_id);
        $stmt->execute();
        $stmt->close();
    }
}

// 4. جلب المنتجات المعلقة
$query = "SELECT products.*, vendors.store_name 
          FROM products 
          JOIN vendors ON products.vendor_id_fk = vendors.vendor_id 
          WHERE products.approved_by_admin = 'pending' 
          ORDER BY products.product_id DESC";

$result = $conn->query($query);
$pending_count = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nashmi store | Approval Queue</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-red: #d72229;
            --night: #170505;
            --clay: #a76f58;
            --bg-soft: #f8f9fa;
            --white: #ffffff;
            --success: #00b894;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-soft); color: var(--night); display: flex; }

        .sidebar { width: 280px; background: var(--night); height: 100vh; position: fixed; padding: 40px 20px; color: white; z-index: 1000; }
        .sidebar-logo { font-size: 1.8rem; font-weight: 800; margin-bottom: 50px; text-align: center; letter-spacing: -1px; }
        .sidebar-logo span { color: var(--primary-red); }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { color: rgba(255,255,255,0.6); text-decoration: none; padding: 16px 22px; display: flex; align-items: center; gap: 15px; border-radius: 20px; transition: var(--transition); font-weight: 600; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: rgba(215, 34, 41, 0.1); color: white; }
        .sidebar ul li a.active { background: var(--primary-red); box-shadow: 0 10px 20px rgba(215, 34, 41, 0.2); }

        .main-content { margin-left: 280px; width: calc(100% - 280px); padding: 50px; animation: fadeIn 0.8s ease-out; }
        .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .dash-header h1 { font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; }
        .highlight { color: var(--primary-red); }
        
        .pending-count span { 
            background: var(--white); padding: 12px 25px; border-radius: 18px; 
            font-weight: 700; box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            border: 1px solid rgba(167, 111, 88, 0.1);
        }

        .msg-alert { padding: 18px 25px; border-radius: 20px; margin-bottom: 35px; font-weight: 700; animation: slideIn 0.5s ease; border: 1px solid transparent; }
        .msg-alert.success { background: #e6fffb; color: var(--success); border-color: var(--success); }
        .msg-alert.error { background: #fff1f0; color: var(--primary-red); border-color: var(--primary-red); }

        .approval-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; }
        .approval-card { 
            background: var(--white); border-radius: 35px; overflow: hidden; 
            box-shadow: 0 20px 40px rgba(23, 5, 5, 0.03); 
            transition: var(--transition); border: 1px solid rgba(167, 111, 88, 0.05);
            display: flex; flex-direction: column;
        }

        .approval-card:hover { transform: translateY(-12px); box-shadow: 0 30px 60px rgba(23, 5, 5, 0.08); }

        .product-preview { position: relative; height: 240px; overflow: hidden; background: #fff; }
        .product-preview img { width: 100%; height: 100%; object-fit: contain; transition: var(--transition); }
        .approval-card:hover .product-preview img { transform: scale(1.05); }
        
        .vendor-tag { 
            position: absolute; bottom: 15px; left: 15px; 
            background: rgba(23, 5, 5, 0.85); color: white; 
            padding: 8px 15px; border-radius: 12px; font-size: 0.7rem; 
            font-weight: 700; backdrop-filter: blur(8px);
        }

        .card-details { padding: 30px; flex-grow: 1; display: flex; flex-direction: column; }
        .price-tag { 
            align-self: flex-start; background: rgba(215, 34, 41, 0.05); 
            color: var(--primary-red); font-weight: 800; padding: 6px 14px; 
            border-radius: 10px; margin-bottom: 15px; font-size: 0.9rem;
        }
        .card-details h3 { font-size: 1.3rem; margin-bottom: 10px; color: var(--night); font-weight: 800; letter-spacing: -0.5px; }
        .desc-text { font-size: 0.9rem; color: var(--clay); line-height: 1.6; margin-bottom: 25px; flex-grow: 1; }

        .approval-actions { display: flex; gap: 12px; }
        .btn-action { 
            flex: 1; text-decoration: none; padding: 14px; border-radius: 18px; 
            font-weight: 800; font-size: 0.85rem; transition: var(--transition); 
            display: flex; align-items: center; justify-content: center; gap: 8px; 
        }
        
        .btn-accept { background: var(--night); color: white; }
        .btn-accept:hover { background: var(--success); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0, 184, 148, 0.2); }
        
        .btn-reject { background: #f1f2f6; color: var(--night); }
        .btn-reject:hover { background: var(--primary-red); color: white; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(215, 34, 41, 0.2); }

        .empty-state { grid-column: 1 / -1; text-align: center; padding: 100px 20px; background: white; border-radius: 40px; border: 2px dashed #eee; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideIn { from { transform: translateX(-20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        @media (max-width: 1024px) {
            .sidebar { width: 100px; }
            .sidebar-logo, .sidebar ul li a span { display: none; }
            .main-content { margin-left: 100px; width: calc(100% - 100px); }
        }
    </style>
</head>
<body>

   <nav class="sidebar">
        <div class="sidebar-logo">Store<span>نشمي</span></div>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></a></li>
            <li><a href="approve.php" class="active"><i class="fa-solid fa-shield-check"></i> <span>Approve Products</span></a></li>
            <li><a href="category.php"><i class="fa-solid fa-layer-group"></i> <span>Categories</span></a></li>
            <li style="margin-top: 50px;"><a href="log_out.php" style="color: #ffbaba;"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a></li>
        </ul>
    </nav>

    <main class="main-content">
        <header class="dash-header">
            <div class="header-info">
                <h1>Review <span class="highlight">Protocol</span></h1>
                <p style="color: var(--clay); font-weight: 500;">Validate and certify new marketplace submissions.</p>
            </div>
            <div class="pending-count">
                <span>In Queue: <strong style="color: var(--primary-red);"><?php echo $pending_count; ?></strong></span>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="msg-alert <?php echo ($msg_type == 'success') ? 'success' : 'error'; ?>">
                <i class="fa-solid <?php echo ($msg_type == 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>" style="margin-right: 10px;"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <section class="approval-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <div class="approval-card">
                    <div class="product-preview">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Product">
                        <span class="vendor-tag"><i class="fa-solid fa-store" style="margin-right: 5px;"></i> <?php echo htmlspecialchars($row['store_name']); ?></span>
                    </div>
                    
                    <div class="card-details">
                        <span class="price-tag"><?php echo number_format($row['product_price'], 2); ?> JOD</span>
                        <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                        <p class="desc-text"><?php echo htmlspecialchars($row['product_description']); ?></p>
                        
                        <div class="approval-actions">
                            <a href="?action=accept&id=<?php echo $row['product_id']; ?>" 
                               class="btn-action btn-accept" 
                               onclick="return confirm('Accept this product to the main store?')">
                               <i class="fa-solid fa-check-double"></i> Accept
                            </a>
                            
                            <a href="?action=reject&id=<?php echo $row['product_id']; ?>" 
                               class="btn-action btn-reject" 
                               onclick="return confirm('Reject and notify the vendor?')">
                               <i class="fa-solid fa-ban"></i> Reject
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <h2>Queue Cleared!</h2>
                    <p style="color: var(--clay);">All submissions have been processed. Good work!</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

</body> 
</html>