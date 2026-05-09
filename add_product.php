<?php
session_start();

// 1. حماية الصفحة والتأكد من هوية الفيندور
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: login.php"); 
    exit();
}

$v_id = $_SESSION['user_id'];

// إعدادات قاعدة البيانات
$host = "localhost";
$user = "root"; 
$pass = "Zz0795426555$"; 
$db   = "multivendor_marketplace"; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
mysqli_set_charset($conn, "utf8mb4");

/**
 * دالة التحقق الذكي بواسطة Gemini 3 Flash
 * تم تحسين الـ Prompt ليفهم العلاقة المنطقية بين المنتج والكاتوغري
 */
function checkProductWithAI($name, $desc, $vendorCatName) {
    $apiKey = "AIzaSyD4A0gvCW0Q2zYpdSV_geTf1NOS_xF3mxA"; 
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=" . $apiKey;

    // الـ Prompt الجديد: يركز على الربط المنطقي والسياق
    $prompt = "Act as a smart marketplace auditor. 
               The vendor's shop category is: '$vendorCatName'.
               The product they want to list is: '$name'.
               Product Description: '$desc'.
               
               Task: Determine if this product logically belongs to the '$vendorCatName' category.
               Logic: 
               - Use semantic understanding (e.g., if category is 'Electronics', then 'Cables' or 'Cases' are APPROVED).
               - If the category is 'Food', then 'Snacks', 'Drinks', or 'Spices' are APPROVED.
               - Be flexible: Approve if the item is a known sub-category, accessory, or related component of the main category.
               - ONLY reject (REVIEW) if the item is completely irrelevant (e.g., selling 'Furniture' in a 'Grocery' store).
               
               Reply with ONLY one word: 'APPROVED' or 'REVIEW'.";

    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    $decision = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'REVIEW';
    return trim(strtoupper($decision));
}

$message = "";
$message_type = "error";

// 2. معالجة البيانات عند الإرسال
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_btn'])) {
    $name  = trim($_POST['prod_name']);
    $desc  = trim($_POST['description']);
    $price = $_POST['price'];
    $qty   = $_POST['stock'];
    
    // جلب اسم الكاتوغري الخاص بالفيندور
    $v_stmt = $conn->prepare("
        SELECT c.category_name 
        FROM vendors v 
        JOIN categories c ON v.category_ID_FK = c.category_id 
        WHERE v.vendor_id = ?
    ");
    $v_stmt->bind_param("i", $v_id);
    $v_stmt->execute();
    $v_res = $v_stmt->get_result();
    $v_row = $v_res->fetch_assoc();
    $v_category_name = $v_row['category_name'] ?? 'General';

    // إعدادات الصورة
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $image_name = time() . "_" . basename($_FILES["prod_image"]["name"]);
    $target_file = $target_dir . $image_name;

    if(getimagesize($_FILES["prod_image"]["tmp_name"]) !== false) {
        if (move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file)) {
            
            // استشارة الذكاء الاصطناعي
            $ai_decision = checkProductWithAI($name, $desc, $v_category_name);
            $final_status = (strpos($ai_decision, 'APPROVED') !== false) ? "approved" : "pending";

            $sql = "INSERT INTO products (product_name, product_description, product_price, product_quantity, image_url, approved_by_admin, product_created_at, vendor_id_fk) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssdissi", $name, $desc, $price, $qty, $target_file, $final_status, $v_id);
                if ($stmt->execute()) {
                    if ($final_status == "approved") {
                        $message = "Smart Success! Product automatically approved for $v_category_name.";
                        $message_type = "success";
                    } else {
                        $message = "Listing received. AI flagged it for admin review as it seems outside your category.";
                        $message_type = "success";
                    }
                } else { $message = "Database error: Failed to save product."; }
                $stmt->close();
            }
        } else { $message = "Upload failed. Check directory permissions."; }
    } else { $message = "Invalid image file."; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nashmi store | Smart Listing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #d72229; --clay: #a76f58; --night: #170505;
            --bg-soft: #f8f9fa; --white: #ffffff; --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-soft); color: var(--night); display: flex; min-height: 100vh; }
        
        /* --- Sidebar --- */
        .sidebar { width: 280px; background: var(--night); color: white; padding: 40px 20px; position: fixed; height: 100vh; }
        .sidebar-logo { font-size: 1.8rem; font-weight: 800; margin-bottom: 50px; text-align: center; }
        .sidebar-logo span { color: var(--primary-red); }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { color: rgba(255,255,255,0.6); text-decoration: none; padding: 16px 22px; display: flex; align-items: center; gap: 15px; border-radius: 20px; transition: var(--transition); font-weight: 600; }
        .sidebar ul li a.active { background: var(--primary-red); color: white; }

        /* --- Main Content --- */
        .main-content { margin-left: 280px; padding: 50px; width: calc(100% - 280px); }
        .form-card { background: var(--white); padding: 45px; border-radius: 40px; box-shadow: 0 20px 50px rgba(23, 5, 5, 0.04); max-width: 950px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .full-width { grid-column: span 2; }
        
        .form-group label { display: block; margin-bottom: 12px; font-weight: 700; color: var(--clay); font-size: 0.85rem; text-transform: uppercase; }
        .form-group input, .form-group textarea { width: 100%; padding: 16px 20px; border: 2px solid #f1f5f9; border-radius: 18px; background: #fafbfc; font-size: 1rem; transition: var(--transition); }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--primary-red); outline: none; background: white; }

        .image-preview-box { border: 2px dashed #e2e8f0; padding: 40px; text-align: center; border-radius: 25px; cursor: pointer; background: #fafbfc; transition: var(--transition); }
        .image-preview-box:hover { border-color: var(--primary-red); }
        #output-image { max-width: 100%; height: 200px; object-fit: contain; border-radius: 15px; display: none; margin-top: 15px; }
        
        .btn-upload { background: var(--night); color: white; border: none; padding: 18px 40px; border-radius: 20px; cursor: pointer; font-weight: 800; width: 100%; transition: var(--transition); margin-top: 30px; font-size: 1.1rem; }
        .btn-upload:hover { background: var(--primary-red); transform: translateY(-3px); }
        
        .alert { padding: 20px; border-radius: 20px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; font-weight: 700; }
        .success { background: #f0fff4; color: #2d8a4e; border: 1px solid #c6f6d5; }
        .error { background: #fff5f5; color: var(--primary-red); border: 1px solid #fed7d7; }
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
                    <li><a href="product_status.php" style="color:white; font-size: 0.9rem; opacity: 0.6;">• Status</a></li>
                    <li><a href="add_product.php" style="color:white; font-size: 0.9rem; font-weight: 800;">• Add New</a></li>
                    <li><a href="delete_update_product.php" style="color:white; font-size: 0.9rem; opacity: 0.6;">• Edit List</a></li>
                </ul>
            </li>
            <li><a href="orders.php"><i class="fa-solid fa-box"></i> Orders</a></li>
            <li><a href="vendor_profile.php"><i class="fa-solid fa-user-gear"></i> Profile</a></li>
            <li style="margin-top: 40px;"><a href="log_out.php" style="color: #ffbaba;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <header style="margin-bottom: 40px;">
            <h1 style="font-size: 2.5rem; font-weight: 800;">Smart Listing <span style="color: var(--primary-red);">AI</span></h1>
            <p style="color: var(--clay);">Semantic Category Verification powered by Gemini 3 Flash.</p>
        </header>

        <section class="form-card">
            <?php if ($message): ?>
                <div class="alert <?= $message_type ?>">
                    <i class="fa-solid <?= $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form action="add_product.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Title</label>
                        <input type="text" name="prod_name" placeholder="e.g. Wired Headphones" required>
                    </div>
                    <div class="form-group">
                        <label>Price (JOD)</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Initial Stock</label>
                        <input type="number" name="stock" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Detailed Description</label>
                        <textarea name="description" rows="4" placeholder="Explain what this product is..." required></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label>Product Visual</label>
                        <div class="image-preview-box" onclick="document.getElementById('file-input').click()">
                            <input type="file" name="prod_image" id="file-input" hidden accept="image/*" onchange="preview(event)" required>
                            <div id="upload-placeholder">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2.5rem; color: var(--clay); margin-bottom: 10px;"></i>
                                <p style="font-weight: 600;">Click to upload image</p>
                            </div>
                            <img id="output-image">
                        </div>
                    </div>
                </div>
                <button type="submit" name="upload_btn" class="btn-upload">
                    <i class="fa-solid fa-bolt"></i> Run AI Check & Publish
                </button>
            </form>
        </section>
    </div>

    <script>
        function preview(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('output-image');
                const placeholder = document.getElementById('upload-placeholder');
                output.src = reader.result;
                output.style.display = 'block';
                placeholder.style.display = 'none';
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>