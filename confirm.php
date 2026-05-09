<?php
session_start();

// 1. الاتصال بقاعدة البيانات
$conn = mysqli_connect("localhost", "root", "Zz0795426555$", "multivendor_marketplace");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");

// 2. استقبال رقم الطلب
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 3. جلب بيانات الطلب
$query = "SELECT * FROM orders WHERE order_id = $order_id";
$result = mysqli_query($conn, $query);
$order_data = mysqli_fetch_assoc($result);

if (!$order_data) {
    header("Location: index.php");
    exit();
}

// 4. جلب طريقة الدفع
$receipt_query = "SELECT payement_method FROM reciept WHERE order_id_fk = $order_id";
$receipt_res = mysqli_query($conn, $receipt_query);
$receipt_data = mysqli_fetch_assoc($receipt_res);
$payment_method = $receipt_data ? $receipt_data['payement_method'] : 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed | Nashmi store</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-red: #d72229;
            --deep-maroon: #770e13;
            --earth-tan: #a76f58;
            --chocolate: #5d382f;
            --rich-black: #170505;
            --soft-bg: #fdfbfb;
            --ease: cubic-bezier(0.23, 1, 0.32, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body { 
            background: linear-gradient(135deg, #fdfbfb 0%, #f5f0ee 100%);
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            padding: 40px 20px;
        }

        .success-container { 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(20px);
            padding: 60px 40px; 
            border-radius: 40px; 
            box-shadow: 0 40px 100px rgba(23, 5, 5, 0.1); 
            text-align: center; 
            max-width: 600px; 
            width: 100%;
            border: 1px solid rgba(167, 111, 88, 0.15);
            animation: slideUp 0.8s var(--ease);
        }

        /* Success Icon Animation */
        .success-icon-wrapper {
            width: 120px;
            height: 120px;
            background: var(--primary-red);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            margin: 0 auto 35px;
            position: relative;
            box-shadow: 0 15px 35px rgba(215, 34, 41, 0.3);
        }

        .success-icon-wrapper::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid var(--primary-red);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        h1 { 
            color: var(--rich-black); 
            font-size: 2.2rem; 
            margin-bottom: 15px; 
            font-weight: 800; 
            letter-spacing: -1px;
        }
        
        p { color: var(--earth-tan); line-height: 1.7; font-size: 1.1rem; font-weight: 500; }

        .order-info-box { 
            background: #fff; 
            border-radius: 28px; 
            padding: 35px; 
            margin: 40px 0; 
            text-align: left; 
            border: 1px solid rgba(167, 111, 88, 0.1);
        }

        .order-id-badge {
            background: var(--rich-black);
            color: white;
            display: inline-block;
            padding: 8px 20px;
            border-radius: 100px;
            font-size: 0.9rem;
            margin-bottom: 25px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .detail-row { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 18px; 
            font-size: 1rem; 
            border-bottom: 1px dashed rgba(167, 111, 88, 0.2);
            padding-bottom: 12px;
        }

        .detail-row:last-child { border: none; padding: 0; margin: 0; }
        .detail-row span { color: var(--earth-tan); font-weight: 600; }
        .detail-row strong { color: var(--chocolate); font-weight: 800; }

        .price-total { color: var(--primary-red) !important; font-size: 1.3rem; }

        .btn-group { display: flex; gap: 15px; margin-top: 45px; }
        
        .btn {
            flex: 1;
            padding: 20px 25px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 800;
            font-size: 1rem;
            transition: all 0.4s var(--ease);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .primary-btn { 
            background: var(--primary-red); 
            color: white; 
            box-shadow: 0 15px 30px rgba(215, 34, 41, 0.2); 
        }
        .primary-btn:hover { 
            background: var(--deep-maroon); 
            transform: translateY(-5px) scale(1.02); 
            box-shadow: 0 20px 40px rgba(119, 14, 19, 0.3); 
        }

        .secondary-btn { 
            background: transparent; 
            color: var(--chocolate); 
            border: 2px solid var(--earth-tan); 
        }
        .secondary-btn:hover { 
            background: var(--soft-bg); 
            border-color: var(--chocolate);
            transform: translateY(-5px);
        }

        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(40px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        
        @keyframes pulse { 
            0% { transform: scale(1); opacity: 0.5; } 
            70% { transform: scale(1.5); opacity: 0; } 
            100% { transform: scale(1.5); opacity: 0; } 
        }

        @media (max-width: 600px) {
            .btn-group { flex-direction: column; }
            .success-container { padding: 40px 25px; }
            h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

    <div class="success-container">
        <div class="success-icon-wrapper">
            <i class="fa-solid fa-check"></i>
        </div>

        <h1>Thank You!</h1>
        <p>Your order has been placed successfully. We are currently preparing your items for shipment to <b><?php echo htmlspecialchars($order_data['shipping_address']); ?></b>.</p>

        <div class="order-info-box">
            <div class="order-id-badge">Order ID: #NSH-<?php echo $order_id; ?></div>
            
            <div class="detail-row">
                <span>Transaction Date</span>
                <strong><?php echo date('M d, Y • H:i', strtotime($order_data['order_date'])); ?></strong>
            </div>
            
            <div class="detail-row">
                <span>Payment Method</span>
                <strong style="text-transform: uppercase;"><?php echo str_replace('_', ' ', $payment_method); ?></strong>
            </div>
            
            <div class="detail-row">
                <span>Total Amount Paid</span>
                <strong class="price-total"><?php echo number_format($order_data['grand_total'], 2); ?> JOD</strong>
            </div>
        </div>

        <div class="btn-group">
            <a href="index.php" class="btn primary-btn">
                Continue Shopping <i class="fa-solid fa-bag-shopping"></i>
            </a>
            <a href="tracking.php" class="btn secondary-btn">
                Track Order <i class="fa-solid fa-truck-fast"></i>
            </a>
        </div>


    </div>

</body>
</html>