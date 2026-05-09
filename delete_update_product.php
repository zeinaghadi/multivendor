<?php
session_start();

// 1. Protection & Database
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: login.php");
    exit();
}
$v_id = $_SESSION['user_id'];

$host = "localhost"; $user = "root"; $pass = "Zz0795426555$"; $db = "multivendor_marketplace"; 
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8mb4");

$message = "";
$message_type = "success";

// --- Delete Logic ---
if (isset($_POST['confirm_delete'])) {
    $delete_id = mysqli_real_escape_string($conn, $_POST['product_to_delete']);
    // التأكد أن الحذف يتم فقط لمنتج يخص الفيندور وحالته approved
    $sql = "DELETE FROM products WHERE product_id = '$delete_id' AND vendor_id_fk = '$v_id' AND approved_by_admin = 'approved'";
    if ($conn->query($sql)) {
        $message = "Product removed successfully.";
        $message_type = "success";
    } else {
        $message = "Cannot delete: Product is linked to orders or not in approved status.";
        $message_type = "error";
    }
}

// --- Bulk Update Logic ---
if (isset($_POST['update_all'])) {
    if (isset($_POST['product_id'])) {
        foreach ($_POST['product_id'] as $index => $id) {
            $id = mysqli_real_escape_string($conn, $id);
            $name = mysqli_real_escape_string($conn, $_POST['name'][$index]);
            $price = $_POST['price'][$index];
            $stock = $_POST['stock'][$index];

            // التعديل يتم فقط للمنتجات الـ approved
            $update_query = "UPDATE products SET product_name='$name', product_price='$price', product_quantity='$stock' 
                             WHERE product_id='$id' AND vendor_id_fk = '$v_id' AND approved_by_admin = 'approved'";
            $conn->query($update_query);
        }
        $message = "Inventory updated successfully!";
        $message_type = "success";
    }
}

// التعديل الأساسي هنا: جلب فقط المنتجات الـ approved
$result = $conn->query("SELECT * FROM products WHERE vendor_id_fk = '$v_id' AND approved_by_admin = 'approved' ORDER BY product_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nashmi store | Manage Approved Products</title>
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
        .main-content { margin-left: 280px; padding: 50px; width: calc(100% - 280px); animation: fadeIn 0.8s ease-out; }

        header { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 40px; 
        }
        header h1 { font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; }
        header h1 span { color: var(--primary-red); }

        .btn-save-all { 
            background: var(--night); color: white; border: none; 
            padding: 14px 30px; border-radius: 18px; cursor: pointer; 
            font-weight: 800; transition: var(--transition);
            display: flex; align-items: center; gap: 10px;
            box-shadow: 0 10px 20px rgba(23, 5, 5, 0.1);
        }
        .btn-save-all:hover { 
            background: var(--primary-red); transform: translateY(-3px); 
            box-shadow: 0 15px 30px rgba(215, 34, 41, 0.25);
        }

        .inventory-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; }

        .card { 
            background: var(--white); padding: 25px; border-radius: 35px; 
            box-shadow: 0 20px 50px rgba(23, 5, 5, 0.03);
            position: relative; transition: var(--transition);
            border: 1px solid rgba(167, 111, 88, 0.05);
            animation: slideUp 0.7s ease-out;
        }
        .card:hover { transform: translateY(-10px); box-shadow: 0 30px 60px rgba(23, 5, 5, 0.08); }

        .card-img { 
            width: 100%; height: 200px; object-fit: cover; 
            border-radius: 25px; margin-bottom: 20px; background: #f1f5f9;
        }

        .btn-del-floating {
            position: absolute; top: 35px; right: 35px;
            background: rgba(255, 255, 255, 0.9); color: var(--primary-red);
            border: none; width: 42px; height: 42px; border-radius: 14px;
            cursor: pointer; transition: var(--transition); backdrop-filter: blur(10px);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1); font-size: 1.1rem;
        }
        .btn-del-floating:hover { background: var(--primary-red); color: white; transform: rotate(15deg) scale(1.1); }

        .card-group { margin-bottom: 15px; }
        .card-group label { 
            display: flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 800; 
            color: var(--clay); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;
        }
        .card-input {
            width: 100%; padding: 12px 18px; border: 2px solid #f1f5f9;
            border-radius: 16px; background: #fafbfc; font-family: inherit;
            box-sizing: border-box; transition: var(--transition); font-size: 0.95rem; font-weight: 600;
        }
        .card-input:focus { border-color: var(--primary-red); background: white; outline: none; box-shadow: 0 8px 20px rgba(215, 34, 41, 0.05); }

        .alert { 
            padding: 20px; border-radius: 20px; margin-bottom: 30px; 
            display: flex; align-items: center; gap: 15px; font-weight: 700;
        }
        .success { background: #f0fff4; color: #2d8a4e; border: 1px solid #c6f6d5; }
        .error { background: #fff5f5; color: var(--primary-red); border: 1px solid #fed7d7; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-logo">Store<span>نشمي</span></div>
        <ul>
            <li><a href="vendor_dashboard.php"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a></li>
            <li>
                <a href="#" class="active"><i class="fa-solid fa-tags"></i> <span>Products</span></a>
                <ul style="list-style: none; padding-left: 25px; margin-top: 10px;">
                    <li><a href="product_status.php" style="font-size: 0.9rem; opacity: 0.6;">• Status</a></li>
                    <li><a href="add_product.php" style="font-size: 0.9rem; opacity: 0.6;">• Add New</a></li>
                    <li><a href="delete_update_product.php" style="color:white; font-size: 0.9rem;">• Edit List</a></li>
                </ul>
            </li>
            <li><a href="orders.php"><i class="fa-solid fa-box"></i> <span>Orders</span></a></li>
            <li><a href="vendor_profile.php"><i class="fa-solid fa-user-gear"></i> <span>Profile</span></a></li>
            <li style="margin-top: 40px;"><a href="log_out.php" style="color: #ffbaba;"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </nav>

    <div class="main-content">
        <header>
            <div>
                <h1>Approved <span>Inventory</span></h1>
                <p style="color: var(--clay); font-weight: 500; margin-top: 5px;">Only products approved by Admin are shown here for editing.</p>
            </div>
            <?php if($result->num_rows > 0): ?>
            <button type="submit" form="bulk-form" name="update_all" class="btn-save-all">
                <i class="fa-solid fa-floppy-disk"></i> Deploy All Changes
            </button>
            <?php endif; ?>
        </header>

        <?php if ($message): ?>
            <div class="alert <?= $message_type ?>">
                <i class="fa-solid <?= $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form id="bulk-form" method="POST">
            <div class="inventory-grid">
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="card">
                        <button type="submit" name="confirm_delete" form="delete-form-<?= $row['product_id'] ?>" class="btn-del-floating" title="Remove Product">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <img src="<?= $row['image_url'] ?>" class="card-img" onerror="this.src='https://via.placeholder.com/400x300?text=Nashmi store+Product'">

                        <div class="card-group">
                            <label><i class="fa-solid fa-heading"></i> Product Title</label>
                            <input type="text" name="name[]" value="<?= htmlspecialchars($row['product_name']) ?>" class="card-input">
                        </div>

                        <div style="display: flex; gap: 15px;">
                            <div class="card-group" style="flex: 1.2;">
                                <label><i class="fa-solid fa-tag"></i> Price (JOD)</label>
                                <input type="number" name="price[]" step="0.01" value="<?= $row['product_price'] ?>" class="card-input">
                            </div>
                            <div class="card-group" style="flex: 0.8;">
                                <label><i class="fa-solid fa-layer-group"></i> Qty</label>
                                <input type="number" name="stock[]" value="<?= $row['product_quantity'] ?>" class="card-input">
                            </div>
                        </div>

                        <input type="hidden" name="product_id[]" value="<?= $row['product_id'] ?>">
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: span 3; text-align: center; padding: 50px; background: white; border-radius: 30px;">
                        <i class="fa-solid fa-box-open" style="font-size: 3rem; color: #eee; margin-bottom: 20px;"></i>
                        <p style="color: var(--clay); font-weight: 600;">No approved products found in your inventory.</p>
                    </div>
                <?php endif; ?>
            </div>
        </form>

        <?php 
            $result->data_seek(0);
            while($row = $result->fetch_assoc()): 
        ?>
        <form id="delete-form-<?= $row['product_id'] ?>" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this approved item?');" style="display:none;">
            <input type="hidden" name="product_to_delete" value="<?= $row['product_id'] ?>">
            <input type="hidden" name="confirm_delete" value="1">
        </form>
        <?php endwhile; ?>
    </div>

    <script>
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(a => {
                a.style.transition = "opacity 0.8s ease, transform 0.8s ease";
                a.style.opacity = "0";
                a.style.transform = "translateY(-10px)";
                setTimeout(() => a.remove(), 800);
            });
        }, 4000);
    </script>
</body>
</html>