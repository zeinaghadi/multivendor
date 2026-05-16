<?php
session_start();

$db_host = "localhost";
$db_user = "root";
$db_pass = "Zz0795426555$";
$db_name = "multivendor_marketplace";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8");


if (!isset($_SESSION['user_id'])) {
    header("Location: test_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Actions
if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $action = $_GET['action'];

    if ($action == 'cancel') {
        $query = "UPDATE orders SET order_status = 'cancelled' 
                  WHERE order_id = $order_id AND customer_id_fk = $user_id 
                  AND TRIM(LOWER(order_status)) = 'pending'";
        mysqli_query($conn, $query);
        header("Location: tracking.php");
        exit();
    } 
    elseif ($action == 'delivered') {
        $query = "UPDATE orders SET order_status = 'delivered' 
                  WHERE order_id = $order_id AND customer_id_fk = $user_id 
                  AND TRIM(LOWER(order_status)) = 'shipped'";
        
        if (mysqli_query($conn, $query)) {
            $v_query = mysqli_query($conn, "SELECT vendor_id_fk FROM orders WHERE order_id = $order_id");
            $v_data = mysqli_fetch_assoc($v_query);
            $vendor_id = $v_data['vendor_id_fk'];
            header("Location: reviews.php?vendor_id=$vendor_id&order_id=$order_id");
            exit();
        }
    }
}

// Fetch Orders
$sql = "SELECT * FROM orders WHERE customer_id_fk = $user_id ORDER BY order_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Journey</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-red: #d72229;
            --deep-maroon: #770e13;
            --earth-tan: #a76f58;
            --chocolate: #5d382f;
            --rich-black: #170505;
            --soft-bg: #fdfbfb;
            --border-soft: rgba(167, 111, 88, 0.15);
            --ease: cubic-bezier(0.23, 1, 0.32, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body { 
            background: linear-gradient(135deg, #fdfbfb 0%, #f5f0ee 100%); 
            color: var(--rich-black); 
            min-height: 100vh;
            padding: 50px 5%; 
        }
        
        .container { max-width: 900px; margin: auto; }

        /* Smooth Entrance */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .top-nav { display: flex; align-items: center; margin-bottom: 40px; }
        .back-btn { 
            text-decoration: none; 
            color: var(--chocolate); 
            font-weight: 700; 
            background: rgba(255, 255, 255, 0.7);
            padding: 12px 24px;
            border-radius: 18px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.03);
            backdrop-filter: blur(10px);
            transition: 0.4s var(--ease);
            display: flex; align-items: center; gap: 12px;
            border: 1px solid var(--border-soft);
        }
        .back-btn:hover { transform: translateX(-8px); color: var(--primary-red); background: white; }

        h2.page-title { 
            font-size: 2.2rem; 
            font-weight: 800; 
            margin-bottom: 40px; 
            color: var(--rich-black);
            letter-spacing: -1px;
        }

        .order-card { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(15px);
            border-radius: 32px; 
            padding: 40px; 
            margin-bottom: 35px; 
            border: 1px solid var(--border-soft);
            box-shadow: 0 25px 50px rgba(23, 5, 5, 0.06);
            animation: fadeInUp 0.8s var(--ease) forwards;
            position: relative;
        }

        .card-header { 
            display: flex; justify-content: space-between; align-items: flex-start; 
            margin-bottom: 35px; padding-bottom: 25px; 
            border-bottom: 2px dashed var(--border-soft); 
        }

        .order-ref-label { color: var(--earth-tan); font-size: 0.9rem; font-weight: 700; text-transform: uppercase; }
        .order-id { font-size: 1.6rem; font-weight: 800; color: var(--chocolate); margin-top: 5px; }

        /* Stepper UI */
        .stepper { 
            display: flex; justify-content: space-between; 
            position: relative; margin: 50px 0 60px; 
        }
        
        .stepper::before { 
            content: ""; position: absolute; top: 18px; left: 0; 
            width: 100%; height: 6px; background: rgba(167, 111, 88, 0.1); 
            z-index: 1; border-radius: 10px;
        }

        .progress-line {
            position: absolute; top: 18px; left: 0; 
            height: 6px; background: linear-gradient(90deg, var(--deep-maroon), var(--primary-red)); 
            z-index: 1; transition: width 1.2s cubic-bezier(0.34, 1.56, 0.64, 1); border-radius: 10px;
        }

        .step { position: relative; z-index: 2; text-align: center; flex: 1; }
        .step-circle { 
            width: 42px; height: 42px; background: white; 
            border-radius: 50%; margin: 0 auto 15px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 0.95rem; color: var(--earth-tan); font-weight: 800; 
            transition: 0.5s var(--ease); border: 2px solid var(--border-soft);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .step-label { font-size: 0.85rem; font-weight: 700; color: var(--earth-tan); transition: 0.3s; }
        
        .step.active .step-circle { 
            background: var(--primary-red); color: white; border-color: var(--primary-red);
            transform: scale(1.2); box-shadow: 0 0 25px rgba(215, 34, 41, 0.3); 
        }
        .step.active .step-label { color: var(--rich-black); }
        .step.completed .step-circle { background: var(--chocolate); color: white; border-color: var(--chocolate); }

        /* Info Grid */
        .info-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); 
            gap: 20px; margin-top: 40px; 
        }
        .info-item { 
            background: rgba(167, 111, 88, 0.05); padding: 20px; border-radius: 20px; 
            border: 1px solid transparent; transition: 0.3s;
        }
        .info-item:hover { background: white; border-color: var(--border-soft); transform: translateY(-3px); }
        .info-item label { display: block; font-size: 0.75rem; color: var(--earth-tan); text-transform: uppercase; font-weight: 800; margin-bottom: 8px; }
        .info-item span { font-size: 1rem; font-weight: 700; color: var(--chocolate); }

        .footer { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-top: 40px; padding-top: 30px; border-top: 2px solid var(--soft-bg); 
        }

        .btn { 
            padding: 16px 32px; border-radius: 18px; 
            text-decoration: none; font-weight: 800; font-size: 0.95rem; 
            transition: 0.4s var(--ease); 
            display: inline-flex; align-items: center; gap: 12px; cursor: pointer; border: none;
        }
        .btn-confirm { background: var(--primary-red); color: white; box-shadow: 0 12px 25px rgba(215, 34, 41, 0.25); }
        .btn-confirm:hover { background: var(--deep-maroon); transform: translateY(-5px); box-shadow: 0 15px 35px rgba(119, 14, 19, 0.35); }
        
        .btn-cancel { background: white; color: var(--primary-red); border: 2px solid var(--border-soft); }
        .btn-cancel:hover { background: #fff1f2; border-color: var(--primary-red); color: var(--deep-maroon); }

        /* Status Badges */
        .badge {
            padding: 8px 18px; border-radius: 100px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge-delivered { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .badge-pending { background: #fffbeb; color: #92400e; border: 1px solid #fef3c7; }

        /* Animation for Preparing status */
        .pulse-icon {
            width: 12px; height: 12px; background: var(--primary-red); border-radius: 50%; 
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(215, 34, 41, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(215, 34, 41, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(215, 34, 41, 0); }
        }

        @media (max-width: 600px) {
            .stepper { flex-direction: column; gap: 30px; align-items: flex-start; margin-left: 20px; }
            .stepper::before, .progress-line { width: 4px; height: 100%; left: 19px; top: 0; }
            .step { display: flex; align-items: center; gap: 20px; text-align: left; }
            .step-circle { margin: 0; }
            .footer { flex-direction: column; gap: 20px; text-align: center; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-nav">
        <a href="index.php" class="back-btn"><i class="fa-solid fa-house"></i> Back Home</a>
    </div>

    <h2 class="page-title"><i class="fa-solid fa-map-location-dot" style="color: var(--primary-red); margin-right: 15px;"></i>Track Your Journey</h2>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result)): 
            $status = strtolower(trim($row['order_status']));
            $prog_width = "0%";
            if($status == 'pending') $prog_width = "12%";
            if($status == 'processing') $prog_width = "37%";
            if($status == 'shipped') $prog_width = "62%";
            if($status == 'delivered') $prog_width = "100%";
        ?>
        <div class="order-card">
            <div class="card-header">
                <div>
                    <span class="order-ref-label">Order Reference</span>
                    <h3 class="order-id">#ORD-<?php echo $row['order_id']; ?></h3>
                </div>
                <div style="text-align: right;">
                    <span class="badge <?php echo ($status == 'delivered') ? 'badge-delivered' : 'badge-pending'; ?>">
                        <?php echo $status; ?>
                    </span>
                    <div style="font-size: 0.9rem; color: var(--earth-tan); margin-top: 10px; font-weight: 700;">
                        Placed on <?php echo date("M d, Y", strtotime($row['order_date'])); ?>
                    </div>
                </div>
            </div>

            <?php if ($status != 'cancelled'): ?>
            <div class="stepper">
                <div class="progress-line" style="width: <?php echo $prog_width; ?>;"></div>
                
                <div class="step <?php echo in_array($status, ['pending', 'processing', 'shipped', 'delivered']) ? 'active' : ''; ?> <?php echo in_array($status, ['processing', 'shipped', 'delivered']) ? 'completed' : ''; ?>">
                    <div class="step-circle"><?php echo in_array($status, ['processing', 'shipped', 'delivered']) ? '<i class="fa-solid fa-check"></i>' : '1'; ?></div>
                    <div class="step-label">Ordered</div>
                </div>

                <div class="step <?php echo in_array($status, ['processing', 'shipped', 'delivered']) ? 'active' : ''; ?> <?php echo in_array($status, ['shipped', 'delivered']) ? 'completed' : ''; ?>">
                    <div class="step-circle"><?php echo in_array($status, ['shipped', 'delivered']) ? '<i class="fa-solid fa-check"></i>' : '2'; ?></div>
                    <div class="step-label">Processing</div>
                </div>

                <div class="step <?php echo in_array($status, ['shipped', 'delivered']) ? 'active' : ''; ?> <?php echo in_array($status, ['delivered']) ? 'completed' : ''; ?>">
                    <div class="step-circle"><?php echo ($status == 'delivered') ? '<i class="fa-solid fa-check"></i>' : '3'; ?></div>
                    <div class="step-label">Shipped</div>
                </div>

                <div class="step <?php echo ($status == 'delivered') ? 'active completed' : ''; ?>">
                    <div class="step-circle"><i class="fa-solid fa-gift"></i></div>
                    <div class="step-label">Arrived</div>
                </div>
            </div>
            <?php else: ?>
                <div style="text-align: center; color: var(--primary-red); padding: 30px; font-weight: 800; background: #fff5f5; border-radius: 24px; margin: 20px 0; border: 2px dashed var(--primary-red);">
                    <i class="fa-solid fa-ban" style="font-size: 1.5rem; margin-bottom: 10px;"></i><br>This order has been cancelled.
                </div>
            <?php endif; ?>

            <div class="info-grid">
                <div class="info-item"><label>Total Paid</label><span><?php echo number_format($row['grand_total'], 2); ?> JOD</span></div>
                <div class="info-item"><label>Method</label><span>
                    <?php 
                $current_order_id = $row['order_id']; 
                $r_query = "SELECT payement_method FROM reciept WHERE order_id_fk = $current_order_id";
                $r_result = mysqli_query($conn, $r_query);
                $r_data = mysqli_fetch_assoc($r_result);
                $current_payment = $r_data ? $r_data['payement_method'] : 'N/A';
                echo htmlspecialchars(strtoupper($current_payment)); 
            ?>
        </span>
                </span></div>
                <div class="info-item" style="grid-column: span 2;"><label>Delivering To</label><span><?php echo htmlspecialchars($row['shipping_address'] ?? 'Customer Address, Jordan'); ?></span></div>
            </div>

            <div class="footer">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <?php if ($status == 'processing'): ?>
                        <div class="pulse-icon"></div>
                        <span style="font-weight: 800; font-size: 0.95rem; color: var(--chocolate);">Crafting your order...</span>
                    <?php endif; ?>
                </div>
                
                <div class="actions">
                    <?php if ($status == 'pending'): ?>
                        <a href="tracking.php?action=cancel&order_id=<?php echo $row['order_id']; ?>" class="btn btn-cancel" onclick="return confirm('Cancel this order?')">Cancel Order</a>
                    <?php elseif ($status == 'shipped'): ?>
                        <a href="tracking.php?action=delivered&order_id=<?php echo $row['order_id']; ?>" class="btn btn-confirm">Received My Order <i class="fa-solid fa-check-double"></i></a>
                    <?php elseif ($status == 'delivered'): ?>
                        <span style="color: var(--primary-red); font-weight: 900; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;">
                            <i class="fa-solid fa-heart-circle-check"></i> Order Completed
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align:center; padding: 100px 40px; background: white; border-radius: 40px; border: 1px solid var(--border-soft);">
            <i class="fa-solid fa-box-open fa-5x" style="color: var(--earth-tan); opacity: 0.2; margin-bottom: 30px;"></i>
            <h3 style="color: var(--chocolate); font-size: 1.8rem; font-weight: 800;">No Adventures Yet</h3>
            <p style="color: var(--earth-tan); margin: 15px 0 35px; font-weight: 500;">Your order history is empty. Time to fill it with greatness!</p>
            <a href="index.php" class="btn btn-confirm">Discover Products</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>