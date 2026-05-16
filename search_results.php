<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// 1. الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "Zz0795426555$", "multivendor_marketplace");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// 2. إعدادات Gemini API
$api_Key = "AIzaSyBYypLxQVsAziO0jG8iQUYEyLHcZDNRMcA"; 
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=" . $api_Key;
$search_query = "";
$ai_keywords_list = [];
$error_msg = "";

if (!empty($_POST['query']) || !empty($_POST['camera_image']) || isset($_FILES['image_query'])) {
    
    $user_input = $_POST['query'] ?? "";
    $imgData = "";

    // معالجة صورة الكاميرا أو الرفع
    if (!empty($_POST['camera_image'])) {
        $imgRaw = $_POST['camera_image'];
        $imgData = str_replace(['data:image/jpeg;base64,', ' '], ['', '+'], $imgRaw);
    } elseif (isset($_FILES['image_query']) && $_FILES['image_query']['error'] === 0) {
        $imgData = base64_encode(file_get_contents($_FILES['image_query']['tmp_name']));
    }

    // الـ Prompt لجلب المترادفات
    $prompt = "User search: '$user_input'. Generate 20 related keywords, synonyms, and broader categories in Arabic and English. 
               Example: for 'tissue', provide 'مناديل, محارم, فاين, ورق صحي, Tissues, Napkins, Kleenex, Soft'. 
               If it's an image, identify it and list its components. 
               Return ONLY a comma-separated list of keywords.";

    $response = callGeminiAI($api_url, $prompt, $imgData);
    
    if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
        $raw_text = $response['candidates'][0]['content']['parts'][0]['text'];
        $ai_keywords_list = array_unique(array_map('trim', explode(',', $raw_text)));
        $search_query = !empty($user_input) ? $user_input : "Visual Concept Search";
    } else {
        $ai_keywords_list = [$user_input];
        $search_query = $user_input;
    }
}

// 3. خوارزمية البحث
$results = null;
if (!empty($ai_keywords_list)) {
    $conditions = [];
    foreach ($ai_keywords_list as $word) {
        $safe_word = $conn->real_escape_string($word);
        if (mb_strlen($safe_word) >= 2) {
            $conditions[] = "product_name LIKE '%$safe_word%'";
            $conditions[] = "product_description LIKE '%$safe_word%'";
        }
    }

    if (!empty($conditions)) {
        $sql = "SELECT DISTINCT * FROM products 
                WHERE (" . implode(" OR ", $conditions) . ") 
                AND approved_by_admin='approved' 
                ORDER BY product_id DESC";
        $results = $conn->query($sql);
    }
}

function callGeminiAI($url, $prompt, $base64Img = "") {
    $parts = [["text" => $prompt]];
    if (!empty($base64Img)) {
        $parts[] = ["inline_data" => ["mime_type" => "image/jpeg", "data" => $base64Img]];
    }
    $payload = json_encode(["contents" => [["parts" => $parts]]]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    $data = json_decode($res, true);
    curl_close($ch);
    return $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Smart Search | Nashmi Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary-red: #d72229; --night: #0b0b0b; }
        body { background: var(--night); color: white; font-family: 'Plus Jakarta Sans', sans-serif; padding: 40px; margin: 0; }
        
        .header-search { text-align: center; margin-bottom: 40px; }
        .ai-status { background: rgba(215, 34, 41, 0.1); border: 1px dashed var(--primary-red); padding: 15px; border-radius: 15px; display: inline-block; margin-bottom: 30px; }

        .results-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 30px; 
            max-width: 1300px;
            margin: 0 auto;
        }

        .product-card { 
            background: rgba(255,255,255,0.03); 
            border: 1px solid rgba(255,255,255,0.08); 
            border-radius: 24px; 
            overflow: hidden; 
            transition: 0.4s; 
            text-decoration: none; 
            color: white; 
        }
        .product-card:hover { transform: translateY(-10px); border-color: var(--primary-red); background: rgba(255,255,255,0.06); }
        
        .img-box { width: 100%; height: 230px; background: white; overflow: hidden; }
        .img-box img { width: 100%; height: 100%; object-fit: contain; }
        
        .content { padding: 20px; }
        .price { color: var(--primary-red); font-size: 1.5rem; font-weight: 800; margin: 10px 0; }
        
        .btn-back { display: inline-block; margin-top: 40px; color: white; text-decoration: none; border: 1px solid #333; padding: 10px 25px; border-radius: 50px; transition: 0.3s; }
        .btn-back:hover { background: white; color: var(--night); }
    </style>
</head>
<body>

    <div class="header-search">
        <h1>Search Results</h1>
        <p>You searched for: <strong style="color: var(--primary-red);"><?php echo htmlspecialchars($search_query); ?></strong></p>
        
        <?php if(!empty($ai_keywords_list)): ?>
        <div class="ai-status">
            <i class="fa-solid fa-robot"></i> AI expanded your search to: 
            <span style="color: #ccc;"><?php echo htmlspecialchars(implode(', ', $ai_keywords_list)); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="results-grid">
        <?php if ($results && $results->num_rows > 0): ?>
            <?php while($row = $results->fetch_assoc()): ?>
                <a href="product_detail.php?id=<?php echo $row['product_id']; ?>" class="product-card">
                    <div class="img-box">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                    </div>
                    <div class="content">
                        <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                        <div class="price">JOD <?php echo number_format($row['product_price'], 2); ?></div>
                        <p style="font-size: 0.85rem; color: #aaa;"><?php echo mb_substr($row['product_description'], 0, 80); ?>...</p>
                    </div>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 100px;">
                <i class="fa-solid fa-box-open fa-4x" style="opacity: 0.2;"></i>
                <h2>No products found!</h2>
                <p>Try searching for broader terms like "Food" or "Clothes".</p>
            </div>
        <?php endif; ?>
    </div>

    <div style="text-align: center; margin-bottom: 40px;">
        <a href="index.php" class="btn-back">Back to Home</a>
    </div>

</body>
</html>